<?php declare(strict_types=1);

namespace XRPLWin\XRPLOrderbookReader\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * @see https://github.com/XRPL-Labs/TxMutationParser/blob/main/test/tx1.ts
 * @see https://hash.xrp.fans/D36265AD359D82BDF056CAFE760F9DFF42BB21C308EC3F68C4DE0D707D2FB6B6/json
 */
final class Tx16Test extends TestCase
{
    public function testRipplingTroughOwnAccountCsc()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx1.json');
        $transaction = \json_decode($transaction);
        $account = "rCSCManTZ8ME9EoLrSHHYKW8PPwWMgkwr";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();

        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);

        # Basic info

        //Own account: three balance changes
        $this->assertEquals(3,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type TRADE
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_TRADE,$parsedTransaction['type']);

        $this->assertFalse($parsedTransaction['self']['feePayer']);

        # Event list

        //contains (correct) `primary` entry
        $this->assertArrayHasKey('primary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'counterparty' => 'rnhxcjE1PPCMdiHY9MvAZ13cQnrQh7yCsC',
            'currency' => 'CSC',
            'value' => '1001.99999999999'
        ],$parsedTransaction['eventList']['primary']);

        //contains (correct) `secondary` entry, has 2 merged counterparties
        $this->assertArrayHasKey('secondary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'counterparty' => ['r38UeRHhNLnprf1CjJ3ts4y1TuGCSSY3hL','rB1CbvwR8Ld6zdTJG96nFRnxF8HvDQooe6'],
            'currency' => 'CSC',
            'value' => '-1001.999999999999'
        ],$parsedTransaction['eventList']['secondary']);
        
        # Event flow

        //contains (correct) `start` entry
        $this->assertArrayHasKey('start',$parsedTransaction['eventFlow']);
        $this->assertArrayHasKey('account',$parsedTransaction['eventFlow']['start']);
        $this->assertEquals('rogue5HnPRSszD9CWGSUz8UGHMVwSSKF6',$parsedTransaction['eventFlow']['start']['account']);
       
        //contains (correct) `intermediate` entry
        $this->assertArrayHasKey('intermediate',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => $account,
            'mutations' => [
                'in' => [
                    'counterparty' => "rnhxcjE1PPCMdiHY9MvAZ13cQnrQh7yCsC",
                    'currency' => "CSC",
                    'value' => "1001.99999999999",
                ],
                'out' => [
                    'counterparty' => ['r38UeRHhNLnprf1CjJ3ts4y1TuGCSSY3hL','rB1CbvwR8Ld6zdTJG96nFRnxF8HvDQooe6'],
                    'currency' => "CSC",
                    'value' => "-1001.999999999999",
                ]
            ]
        ],$parsedTransaction['eventFlow']['intermediate']);

        //contains (correct) `end` entry
        $this->assertArrayHasKey('end',$parsedTransaction['eventFlow']);
        $this->assertArrayHasKey('account',$parsedTransaction['eventFlow']['end']);
        $this->assertEquals('rogue5HnPRSszD9CWGSUz8UGHMVwSSKF6',$parsedTransaction['eventFlow']['end']['account']);

    }
}