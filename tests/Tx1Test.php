<?php declare(strict_types=1);

namespace XRPLWin\XRPLOrderbookReader\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

final class Tx1Test extends TestCase
{
    public function testTx1_RipplingTroughOwnAccount()
    { 
        //https://hash.xrp.fans/D36265AD359D82BDF056CAFE760F9DFF42BB21C308EC3F68C4DE0D707D2FB6B6/json

        $transaction = file_get_contents(__DIR__.'/fixtures/tx1.json');
        $transaction = \json_decode($transaction);
        $account = "r38UeRHhNLnprf1CjJ3ts4y1TuGCSSY3hL";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();

        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);

        # Basic info

        //Own account: two balance changes
        $this->assertEquals(2,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type TRADE
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_TRADE,$parsedTransaction['type']);

        # Event list

        //contains (correct) `primary` entry
        $this->assertArrayHasKey('primary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'counterparty' => 'rCSCManTZ8ME9EoLrSHHYKW8PPwWMgkwr',
            'currency' => 'CSC',
            'value' => '1.999999999999'
        ],$parsedTransaction['eventList']['primary']);

        //contains (correct) `secondary` entry
        $this->assertArrayHasKey('secondary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'currency' => 'XRP',
            'value' => '-0.004362'
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
                    'counterparty' => "rCSCManTZ8ME9EoLrSHHYKW8PPwWMgkwr",
                    'currency' => "CSC",
                    'value' => "1.999999999999",
                ],
                'out' => [
                    'currency' => "XRP",
                    'value' => "-0.004362",
                ]
            ]
        ],$parsedTransaction['eventFlow']['intermediate']);

        //contains (correct) `end` entry
        $this->assertArrayHasKey('end',$parsedTransaction['eventFlow']);
        $this->assertArrayHasKey('account',$parsedTransaction['eventFlow']['end']);
        $this->assertEquals('rogue5HnPRSszD9CWGSUz8UGHMVwSSKF6',$parsedTransaction['eventFlow']['end']['account']);

    }
}