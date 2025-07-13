<?php declare(strict_types=1);

namespace XRPLWin\XRPLTxMutatationParser\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * Trading Fees test
 */
final class Tx26Test extends TestCase
{
    public function testMPTPaymentReturnedToIssuer()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx26.json');
        $transaction = \json_decode($transaction);

        //Other account got their mpt clawed back
        $account = "rGepNyxjJbtN75Zb4fgkjQsnv3UUcbp45E"; //account root regular key
        $TxMutationParser = new TxMutationParser($account, $transaction->result, true);
        $parsedTransaction = $TxMutationParser->result();
        $this->assertEquals($account,$parsedTransaction['self']['account']);
        
        //mpt returned to issuer
        $this->assertEquals(0,count($parsedTransaction['self']['balanceChanges']));
        $this->assertEquals(0,count($parsedTransaction['self']['balanceChangesExclFee']));
       
        $this->assertEquals([], $parsedTransaction['self']['balanceChanges']);

        $this->assertEquals([], $parsedTransaction['self']['balanceChangesExclFee']);


    }

    public function testMPTPaymentSender()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx26.json');
        $transaction = \json_decode($transaction);

        //Other account got their mpt clawed back
        $account = "rMdLLyrrh1UC7M5rA4UVvBDjsbzi4Go1yc"; //account root regular key
        $TxMutationParser = new TxMutationParser($account, $transaction->result, true);
        $parsedTransaction = $TxMutationParser->result();
        $this->assertEquals($account,$parsedTransaction['self']['account']);
        
        
        $this->assertEquals(2,count($parsedTransaction['self']['balanceChanges']));
        $this->assertEquals(1,count($parsedTransaction['self']['balanceChangesExclFee']));
            
        $this->assertEquals([
            [
                'mpt_issuance_id' => '0042AB9EAB8A5036CE4DB80D47016F557F9BFC9523985BF1',
                'value' => '-100000',
            ],
            [
                'currency' => 'XRP',
                'value' => '-0.000001',
            ]
        ], $parsedTransaction['self']['balanceChanges']);
        //dd($parsedTransaction['self']['balanceChangesExclFee']);
        $this->assertEquals([
            [
                'mpt_issuance_id' => '0042AB9EAB8A5036CE4DB80D47016F557F9BFC9523985BF1',
                'value' => '-100000',
            ],
        ], $parsedTransaction['self']['balanceChangesExclFee']);


    }


    
}