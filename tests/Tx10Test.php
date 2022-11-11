<?php declare(strict_types=1);

namespace XRPLWin\XRPLOrderbookReader\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * @see https://github.com/XRPL-Labs/TxMutationParser/blob/main/test/tx10.ts
 * @see https://hash.xrp.fans/4598173BFEF787A3F923D5E8E6ECB618F42A0AF38E32BDFA5E4C6EBA49FADE91/json
 */
final class Tx10Test extends TestCase
{
    public function testTradingByPaymentToSelfXRParrot()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx10.json');
        $transaction = \json_decode($transaction);
        $account = "rp65fD8N8fWxhMXwQN1CYVwYPeVofmh3S1";
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
            'value' => "20.172288",
        ],$parsedTransaction['eventList']['primary']);
        
        //contains (correct) `secondary` entry
        $this->assertArrayHasKey('secondary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'counterparty' => 'rhub8VRN55s94qWKDv6jmDy1pUykJzF3wq',
            'currency' => "EUR",
            'value' => "-4",
        ],$parsedTransaction['eventList']['secondary']);

        # Event flow

        //contains (correct) `start` entry
        $this->assertArrayHasKey('start',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => $account,
            'mutation' => [
                'counterparty' => 'rhub8VRN55s94qWKDv6jmDy1pUykJzF3wq',
                'currency' => "EUR",
                'value' => "-4",
            ]
        ],$parsedTransaction['eventFlow']['start']);

        //does not contain `intermediate` entry
        $this->assertArrayNotHasKey('intermediate',$parsedTransaction['eventFlow']);
        
        //contains (correct) `end` entry
        $this->assertArrayHasKey('end',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => $account,
            'mutation' => [
                'currency' => "XRP",
                'value' => "20.172088"
            ]
        ],$parsedTransaction['eventFlow']['end']);

    }
}