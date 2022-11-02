<?php declare(strict_types=1);

namespace XRPLWin\XRPLTxMutatationParser;
use XRPLWin\XRPL\Utilities\BalanceChanges;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * @see https://github.com/XRPL-Labs/TxMutationParser/blob/main/src/index.ts
 */
class TxMutationParser
{
	private readonly string $account;
	private readonly \stdClass $tx;

  public function __construct(string $reference_account, \stdClass $tx)
  {
    $this->account = $reference_account;
    $this->tx = $tx;

    $fee = BigDecimal::of($this->tx->Fee)->exactlyDividedBy(1000000);

    /**
     * Calculate balance changes from meta and own changes
     */
    $bc = new BalanceChanges($this->tx->meta);
    $allBalanceChanges = $bc->result(true);
    $ownBalanceChanges = isset($allBalanceChanges[$this->account]) ? $allBalanceChanges[$this->account] : [];
    $balanceChangeExclFeeOnly = [];
    foreach($ownBalanceChanges['balances'] as $k => $v) {
      if($v['currency'] === 'XRP' && $v['value'] === '-'.(string)$fee && !isset($v['counterparty'])) {
        //pass
      } else {
        $balanceChangeExclFeeOnly[] = $v;
      }
    }
    dd($ownBalanceChanges,$balanceChangeExclFeeOnly);
 
  }
}