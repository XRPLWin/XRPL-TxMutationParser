<?php declare(strict_types=1);

namespace XRPLWin\XRPLTxMutatationParser\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * Trading Fees test
 */
final class Tx23Test extends TestCase
{
    public function testMPTClawbackFromOtherPerspective()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx23.json');
        $transaction = \json_decode($transaction);

        //Other account got their mpt clawed back
        $account = "ra4qNsNJqY92MjEmSPmydz3XqsxQUfNg9k"; //account root regular key
        $TxMutationParser = new TxMutationParser($account, $transaction->result, true);
        $parsedTransaction = $TxMutationParser->result();
        $this->assertEquals($account,$parsedTransaction['self']['account']);
        
        
        $this->assertEquals(1,count($parsedTransaction['self']['balanceChanges']));
        $this->assertEquals(1,count($parsedTransaction['self']['balanceChangesExclFee']));
        //dd(count($parsedTransaction['self']['balanceChangesExclFee']));
    }

    public function testMPTClawback()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx23.json');
        $transaction = \json_decode($transaction);
        $account = "rGepNyxjJbtN75Zb4fgkjQsnv3UUcbp45E"; //account root regular key
        $TxMutationParser = new TxMutationParser($account, $transaction->result, true);
        $parsedTransaction = $TxMutationParser->result();
        
        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);
       
        # Basic info

        //Own account: one balance change
        $this->assertEquals(1,count($parsedTransaction['self']['balanceChanges']));
        
        $this->assertEquals(
            [],
            $parsedTransaction['self']['tradingFees']
        );

    }

    
}