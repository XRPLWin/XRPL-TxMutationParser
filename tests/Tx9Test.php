<?php declare(strict_types=1);

namespace XRPLWin\XRPLOrderbookReader\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * @see https://github.com/XRPL-Labs/TxMutationParser/blob/main/test/tx9.ts
 * @see https://hash.xrp.fans/4AEEDA19D5EC4F902765FD061DCEDEEA63E4E507B5C2E0B17AA281AFD09F05AC/json
 */
final class Tx9Test extends TestCase
{
    public function testOfferCreateInstantTrade()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx9.json');
        $transaction = \json_decode($transaction);
        $account = "rwietsevLFg8XSmG3bEZzFein1g8RBqWDZ";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();
        
        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);
       
        # Basic info

        //Own account: two balance changes
        $this->assertEquals(2,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type TRADE
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_TRADE,$parsedTransaction['type']);

        $this->assertTrue($parsedTransaction['self']['feePayer']);

        # Event list

        //contains (correct) `primary` entry
        $this->assertArrayHasKey('primary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'currency' => "XRP",
            'value' => "0.005896",
        ],$parsedTransaction['eventList']['primary']);
        
        //contains (correct) `secondary` entry
        $this->assertArrayHasKey('secondary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'counterparty' => 'rsoLo2S1kiGeCcn6hCUXVrCpGMWLrRrLZz',
            'currency' => "534F4C4F00000000000000000000000000000000",
            'value' => "-0.0040004",
        ],$parsedTransaction['eventList']['secondary']);

        # Event flow

        //does not contain `start` entry
        $this->assertArrayNotHasKey('start',$parsedTransaction['eventFlow']);

        //contains (correct) `intermediate` entry
        $this->assertArrayHasKey('intermediate',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => $account,
            'mutations' => [
                'in' => [
                    'currency' => "XRP",
                    'value' => "0.005908",
                ],
                'out' => [
                    'counterparty' => 'rsoLo2S1kiGeCcn6hCUXVrCpGMWLrRrLZz',
                    'currency' => "534F4C4F00000000000000000000000000000000",
                    'value' => "-0.0040004",
                ],
    
            ]
        ],$parsedTransaction['eventFlow']['intermediate']);

        //does not contain `end` entry
        $this->assertArrayNotHasKey('end',$parsedTransaction['eventFlow']);
    }
}