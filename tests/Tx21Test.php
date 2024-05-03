<?php declare(strict_types=1);

namespace XRPLWin\XRPLTxMutatationParser\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * PaymentChannelCreate
 * Initiator has regular key but this transaction was signed with initiator account.
 */
final class Tx21Test extends TestCase
{
    public function testPaymentChannelCreateAccountRootRegularkeyNotSigner()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx21.json');
        $transaction = \json_decode($transaction);
        $account = "ravr52zHtsL6JZrWxz4aZe96rffg1ixwGT"; //account root regular key
        $TxMutationParser = new TxMutationParser($account, $transaction->result, true);
        $parsedTransaction = $TxMutationParser->result();

        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);

        # Basic info

        //Own account: one balance change
        $this->assertEquals(0,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type SENT
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_UNKNOWN,$parsedTransaction['type']);
    }
}