<?php declare(strict_types=1);

namespace XRPLWin\XRPLTxMutatationParser\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * Trading Fees test
 */
final class Tx22Test extends TestCase
{
    public function testPaymentWithTradingFees()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx22.json');
        $transaction = \json_decode($transaction);
        $account = "rNHeGnj4kqGSVyFzDcoyi3gsp1bdPuGeNK"; //account root regular key
        $TxMutationParser = new TxMutationParser($account, $transaction->result, true);
        $parsedTransaction = $TxMutationParser->result();
        
        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);
       
        # Basic info

        //Own account: one balance change
        $this->assertEquals(3,count($parsedTransaction['self']['balanceChanges']));

        $this->assertEquals(
            ['53656167756C6C43617368000000000000000000' => '0.000005'],
            $parsedTransaction['self']['tradingFees']
        );
    }
}