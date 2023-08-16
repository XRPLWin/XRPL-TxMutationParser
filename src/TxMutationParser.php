<?php declare(strict_types=1);

namespace XRPLWin\XRPLTxMutatationParser;
use XRPLWin\XRPL\Utilities\BalanceChanges;
use Brick\Math\BigDecimal;
use XRPL_PHP\Core\CoreUtilities as XRPLPHPUtilities;

/**
 * @see https://github.com/XRPL-Labs/TxMutationParser/blob/main/src/index.ts
 */
class TxMutationParser
{
  private readonly string $account;
  private readonly \stdClass $tx;
  private array $result;
  private bool $feePayer = false; //if true then reference_account is fee payer

  const MUTATIONTYPE_SENT = 'SENT'; //Outgoing transaction
  const MUTATIONTYPE_ACCEPT = 'ACCEPT'; //Accept NFT offer
  const MUTATIONTYPE_RECEIVED = 'RECEIVED'; //Incoming transaction
  const MUTATIONTYPE_SET = 'SET'; //Set, eg. Account, Trust Line, Offer, ...
  const MUTATIONTYPE_TRADE = 'TRADE'; //Eg. Trade based on offer, or self to self
  const MUTATIONTYPE_REGULARKEYSIGNER = 'REGULARKEYSIGNER'; //Executed on behalf of someone else (Regular Key)
  const MUTATIONTYPE_UNKNOWN = 'UNKNOWN'; //Could not determine the transaction type (from own context)

  public function __construct(string $reference_account, \stdClass $tx)
  {
    $this->account = $reference_account;
    $this->tx = $tx;

    $feeBD = BigDecimal::of($this->tx->Fee)->exactlyDividedBy(1000000)->stripTrailingZeros();
    $fee = (string)$feeBD;
    $feePayerViaOwnBalanceChanges = false;

    /**
     * Calculate balance changes from meta and own changes
     */
    $bc = new BalanceChanges($this->tx->meta);
    
    $allBalanceChanges = $bc->result(true);
    $ownBalanceChanges = isset($allBalanceChanges[$this->account]) ? $allBalanceChanges[$this->account]['balances'] : [];
    $balanceChangeExclFeeOnly = [];
    
    foreach($ownBalanceChanges as $v) {
      if($v['currency'] === 'XRP' && $v['value'] === '-'.$fee && !isset($v['counterparty'])) {
        $this->feePayer = true;
        $feePayerViaOwnBalanceChanges = true;
      } else {
        $balanceChangeExclFeeOnly[] = $v;
      }
    }
    unset($v);

    /**
     * Get signer from tx public key
     */
    $signer = isset($this->tx->Account) ? $this->tx->Account : null;
    if(!$signer || count($ownBalanceChanges) < 1) {
      // No signer known from Account, or own account has no balance changes.
      // Self is possibly a Regular Key
      $SigningPubKey = isset($this->tx->SigningPubKey) ? $this->tx->SigningPubKey : null;
      
      if(\is_string($SigningPubKey) && $SigningPubKey !== '') {
        $signer = $this->pubkeyToAccount($SigningPubKey);
      }
    }

    /**
     * Determine transaction type from the context of own account
     */
    $type = self::MUTATIONTYPE_UNKNOWN;

    if($signer === $this->account) {
      $type = self::MUTATIONTYPE_REGULARKEYSIGNER;
    }

    if(isset($this->tx->Account) && $this->tx->Account === $this->account) {
      $type = self::MUTATIONTYPE_SENT;
      $this->feePayer = true;
      if($this->tx->TransactionType == 'NFTokenAcceptOffer') {
        $type = self::MUTATIONTYPE_ACCEPT;
      }
    }
    
    if(isset($this->tx->Destination) && $this->tx->Destination === $this->account) {
      $type = self::MUTATIONTYPE_RECEIVED;
    }
    
    /**
     * Payment to self, multiple currencies affected
     */
    if(
      isset($this->tx->Account) && $this->tx->Account === $this->account &&
      isset($this->tx->Destination) && $this->tx->Destination === $this->account
    ) {
      if(count($balanceChangeExclFeeOnly) > 1) {
        $type = self::MUTATIONTYPE_TRADE;
        $this->feePayer = true;
      } 
      /**
       * Payment to self, XRP currecy affected, no fee in XRP balance changes
       */
      elseif($this->feePayer && !$feePayerViaOwnBalanceChanges) {
        $this->feePayer = false;
      }
    }
    
    /**
     * Own balance change count excl. fee only > 1 (so something was exchanged)
     * TX Type = Offer (Trade)
     */
    if(count($balanceChangeExclFeeOnly) > 1 && $this->isOfTypeOfferOrPayment($this->tx->TransactionType)) {
      $type = self::MUTATIONTYPE_TRADE;
    }
    
    /**
     * Own balance change is fee only
     */
    if(count($ownBalanceChanges) === 1 && count($balanceChangeExclFeeOnly) === 0) {
      $type = self::MUTATIONTYPE_SET;
    }
    
    /**
     * Render Event List object
     */
    $eventList = [];
    if(count($ownBalanceChanges) > 0) {
      $eventList['primary'] = $this->significantBalanceChange($ownBalanceChanges,$fee);
      if(count($eventList['primary']) == 0) {
        unset($eventList['primary']);
      } else {
        if(count($balanceChangeExclFeeOnly) > 1) {

          foreach($balanceChangeExclFeeOnly as $change) {
            if($change != $eventList['primary']) { //compare two arrays if they have same key/value pairs
              if(!isset($eventList['secondary']))
                $eventList['secondary'] = $change;
              elseif($eventList['secondary']['currency'] == $change['currency']) { //prevent "XRP" and "currency" mixing
                $eventList['secondary']['value'] = BigDecimal::of($eventList['secondary']['value'])->plus($change['value']);
                if(isset($eventList['secondary']['counterparty']) && !is_array($eventList['secondary']['counterparty']))
                  $eventList['secondary']['counterparty'] = [$eventList['secondary']['counterparty']];
                if(isset($change['counterparty']))
                  $eventList['secondary']['counterparty'][] = $change['counterparty'];
              }  
            }
          }
          if(isset($eventList['secondary']['value']))
            $eventList['secondary']['value'] = (string)$eventList['secondary']['value'];
        }
      }
    }
    
    if(
      $type === self::MUTATIONTYPE_TRADE &&
      isset($eventList['primary']) &&
      isset($eventList['secondary']) &&
      $eventList['primary']['currency'] === $eventList['secondary']['currency'] &&
      BigDecimal::of($eventList['primary']['value'])->abs()->isEqualTo( BigDecimal::of($eventList['secondary']['value'])->abs() )
    ) {
      unset($eventList['primary']);
      unset($eventList['secondary']);
    }

    /**
     * Render event details, min. 1, max. 3 results:
     * 1 = Only self
     *     Set, escrow to self, etc.
     * 2 = From - To
     * 3 = Intermediary, eg. Regular Key signed or async offer consumed
     */
    $eventFlow = [];

    /**
     * Where did the transaction start?
     */
    if(isset($this->tx->Account) && isset($allBalanceChanges[$this->tx->Account])) {
      
      $_balanceChanges = [];
      foreach($allBalanceChanges[$this->tx->Account]['balances'] as $change) {
        if($this->tx->TransactionType == 'NFTokenAcceptOffer') { //allow positive
          $_balanceChanges[] = $change;
        } else {
          if(\substr($change['value'],0,1) === '-')
            $_balanceChanges[] = $change;
        }
      }

      $eventFlow['start'] = [
        'account' => $this->tx->Account,
        'mutation' => $this->significantBalanceChange(
          $_balanceChanges,
          //(
          //  $this->tx->Account === $this->account || 
          //  (isset($this->tx->Destination) && $this->tx->Destination === $this->account)
          //) ? $fee : null
          $fee
        )
      ];
      if($eventFlow['start']['mutation'] === [])
        unset($eventFlow['start']);
    }

    /**
     * Where did the transaction end up?
     */
    if(isset($this->tx->Destination) && isset($allBalanceChanges[$this->tx->Destination])) {
      $_balanceChanges = [];
      foreach($allBalanceChanges[$this->tx->Destination]['balances'] as $change) {
        if(isset($eventFlow['start']['mutation'])) {
          if($change != $eventFlow['start']['mutation'])
            $_balanceChanges[] = $change;
        } 
      }
      
      $mutation = $this->significantBalanceChange(
        $_balanceChanges,
        (
          $this->tx->Destination === $this->account || 
          (isset($this->tx->Account) && $this->tx->Account === $this->account)
        ) ? $fee : null
      );
      
      if(count($mutation) > 0) {
        $eventFlow['end'] = [
          'account' => $this->tx->Destination,
          'mutation' => $mutation
        ];
      }
    }

    /**
     * What happened at an intermediary?
     */
    if($type === self::MUTATIONTYPE_REGULARKEYSIGNER && $signer) {
      $eventFlow['intermediate'] = ['account' => $signer];
    }

    if($type === self::MUTATIONTYPE_UNKNOWN && $this->tx->TransactionType === 'NFTokenAcceptOffer' && count($balanceChangeExclFeeOnly) > 0) {
      $type = self::MUTATIONTYPE_TRADE;
    }

    $isOwnDirectTrade = (
      isset($this->tx->Account) && $this->tx->Account == $this->account && 
      isset($eventFlow['start']) && !isset($eventFlow['end'])
    );

    if(
      (
        ( !isset($this->tx->Destination) || (isset($this->tx->Destination)  && $this->tx->Destination !== $this->account) ) &&
        ( !isset($this->tx->Account)     || (isset($this->tx->Account)      && $this->tx->Account     !== $this->account) )
      ) || $isOwnDirectTrade
    ) {
      $eventFlow['intermediate'] = [
        'account' => $this->account,
        'mutations' => [
          'in' => isset($eventList['primary']) ? $eventList['primary'] : null,
          'out' => isset($eventList['secondary']) ? $eventList['secondary'] : null,
        ]
      ];

      /**
       * If intermediate only one value (in) and negative,
       * then it's `out`
       */
      $in = isset($eventFlow['intermediate']['mutations']['in']) ? $eventFlow['intermediate']['mutations']['in'] : null;
      $out = isset($eventFlow['intermediate']['mutations']['out']) ? $eventFlow['intermediate']['mutations']['out'] : null;
      
      if($in && !$out && \substr($in['value'],0,1) === '-') {
        $eventFlow['intermediate']['mutations']['out'] = $in;
        //unset($eventFlow['intermediate']['mutations']['in']);
        $eventFlow['intermediate']['mutations']['in'] = null;
      }

      if($isOwnDirectTrade && isset($eventFlow['intermediate']) && isset($eventFlow['start'])) {
        unset($eventFlow['start']);
      }
    }

    /**
     * Exclude Fee from primary if primary is positive XRP and this account pays fee
     */
    if($this->feePayer && isset($eventList['primary'])) {
      if($eventList['primary']['currency'] === 'XRP' && \substr($eventList['primary']['value'],0,1) !== '-') { //is positive XRP (fee included)
        //remove Fee (plus positive fee to cancel out) to get real gained amount
        $eventList['primary']['value'] = (string)BigDecimal::of($eventList['primary']['value'])->plus($fee)->stripTrailingZeros();
      }
    }

    /**
     * Balance changes of self without fee
     */
    $ownBalanceChangesExclFee = $ownBalanceChanges;
    if($this->feePayer) {
      foreach($ownBalanceChangesExclFee as $_k => $_o) {
        if(count($_o) == 2) {
          //is XRP
          $_value_xrp = BigDecimal::of($_o['value'])->stripTrailingZeros();
          $_value_xrp_without_fee = $_value_xrp->minus($feeBD);
          if($_value_xrp_without_fee->compareTo(0) == 0) {
            //zero, remove
            unset($ownBalanceChangesExclFee[$_k]);
          } else {
            //non-zero, adjust
            $ownBalanceChangesExclFee[$_k]['value'] = (string)$_value_xrp_without_fee->stripTrailingZeros();
          }
        }
      }
    }
    

    $this->result = [
      'self' => [
        'feePayer' => $this->feePayer,
        'account' => $this->account,
        'balanceChanges' => $ownBalanceChanges,
        'balanceChangesExclFee' => $ownBalanceChangesExclFee,
      ],
      'type' => $type,
      'eventList' => $eventList,
      'eventFlow' => $eventFlow,
      'allBalanceChanges' => $allBalanceChanges
    ];
  }

  private function significantBalanceChange(array $balanceChanges, ?string $fee): array
  {
    if(count($balanceChanges) < 1)
      return [];

    $positiveChanges = [];
    foreach($balanceChanges as $change) {
      if(\substr($change['value'],0,1) !== '-') {
        $positiveChanges[] = $change;
      }
    }
    unset($change);
    
    $positiveChangesNonXRP = [];
    foreach($positiveChanges as $change) {
      if($change['currency'] === 'XRP' && (!isset($change['counterparty']) || (isset($change['counterparty']) && $change['counterparty'] === '') ) ) {
        //skip
      } else {
        $positiveChangesNonXRP[] = $change;
      }
    }

    $nonXRPChanges = [];
    foreach($balanceChanges as $change) {
      if($change['currency'] === 'XRP' && (!isset($change['counterparty']) || (isset($change['counterparty']) && $change['counterparty'] === '') ) ) {
        //skip
      } else {
        $nonXRPChanges[] = $change;
      }
    }

    if(count($positiveChangesNonXRP) > 0) {
      return $positiveChangesNonXRP[0];
    }
    
    if(count($positiveChanges) > 0) {
      
      //Possibly offer accepted and fee paid in the same time, remove fee
      //if($fee && $positiveChanges[0]['currency'] === 'XRP') {
      //  $positiveChanges[0]['value'] = (string)BigDecimal::of($positiveChanges[0]['value'])->minus($fee)->stripTrailingZeros();
      //}
      return $positiveChanges[0];
    }
    
    if(count($nonXRPChanges) > 0) {
      return $nonXRPChanges[0];
    }
    
    /**
     * Fallback to default
     * Possibly XRP sent, if so: exclude fee
     * If fee equals to amount then fallback is zero,
     *  fee is only primary change and we do not record that,
     *  this will keep fee display consistant.
     */
    $fallback = $balanceChanges[0];

    if(
      $fallback['currency'] === 'XRP' &&
      ( !isset($fallback['counterparty']) || (isset($fallback['counterparty']) && $fallback['counterparty'] === '') ) &&
      \substr($fallback['value'],0,1) === '-' &&
      $fee
    ) {
      $fallback['value'] = BigDecimal::of($fallback['value'])->abs()->isEqualTo( BigDecimal::of($fee)->abs() ) 
        ? null //$fallback['value'] //fee equals to !value = zero change
        : (string)BigDecimal::of($fallback['value'])->plus($fee)->stripTrailingZeros();
      if($fallback['value'] === null)
        return [];
      return $fallback;
    }
    return $fallback;
  }

  private function pubkeyToAccount(string $SigningPubKey): string
  {
    return XRPLPHPUtilities::deriveAddress($SigningPubKey);
  }

  /**
   * Check if $type matches words 'payment' or 'offer'
   * @return bool
   */
  private function isOfTypeOfferOrPayment(string $type): bool
  {
    $type = \strtolower($type);

    if( \str_contains($type,'payment') )
      return true;
    if( \str_contains($type,'offer') )
      return true;

    return false;
  }

  public function result(): array
  {
    return $this->result;
  }
}