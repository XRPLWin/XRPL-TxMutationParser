<?php declare(strict_types=1);

namespace XRPLWin\XRPLTxMutatationParser\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * Trading Fees test
 */
final class Tx27Test extends TestCase
{
    public function testMPTEscrowFinishByFinisher()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx27.json');
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

    public function testMPTEscrowFinishByReciever()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx27.json');
        $transaction = \json_decode($transaction);

        //Other account got their mpt clawed back
        $account = "ra4qNsNJqY92MjEmSPmydz3XqsxQUfNg9k"; //account root regular key
        $TxMutationParser = new TxMutationParser($account, $transaction->result, true);
        $parsedTransaction = $TxMutationParser->result();
        $this->assertEquals($account,$parsedTransaction['self']['account']);
        
        //mpt returned to issuer
        $this->assertEquals(1,count($parsedTransaction['self']['balanceChanges']));
        $this->assertEquals(1,count($parsedTransaction['self']['balanceChangesExclFee']));
        
        $this->assertEquals([
            [
                'mpt_issuance_id' => '0042AB9EAB8A5036CE4DB80D47016F557F9BFC9523985BF1',
                'value' => '99415', //got this much unscaled value
            ]
        ], $parsedTransaction['self']['balanceChanges']);
        
        $this->assertEquals([
            [
                'mpt_issuance_id' => '0042AB9EAB8A5036CE4DB80D47016F557F9BFC9523985BF1',
                'value' => '99415', //got this much unscaled value
            ]
        ], $parsedTransaction['self']['balanceChangesExclFee']);


    }
}