<?php declare(strict_types=1);

namespace XRPLWin\XRPLOrderbookReader\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * @see https://github.com/XRPL-Labs/TxMutationParser/blob/main/test/tx3.ts
 * @see https://hash.xrp.fans/E788964F86299E0D5CF9ACD30D0E1DC120BBECA1AC0E10C52FED8EE8368BC9EE/json
 */
final class Tx3Test extends TestCase
{
    public function testPartialPaymentSender()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx3.json');
        $transaction = \json_decode($transaction);
        $account = "rQHYSEyxX3GKK3F6sXRvdd2NHhUqaxtC6F";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();
       

        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);
       
        # Basic info

        //Own account: two balance changes
        $this->assertEquals(2,count($parsedTransaction['self']['balanceChanges']));
        
        //Transaction type SENT
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_SENT,$parsedTransaction['type']);

        $this->assertTrue($parsedTransaction['self']['fee_payer']);

        # Event list

        //contains (correct) `primary` entry
        $this->assertArrayHasKey('primary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'counterparty' => 'rhub8VRN55s94qWKDv6jmDy1pUykJzF3wq',
            'currency' => 'USD',
            'value' => '-0.05'
        ],$parsedTransaction['eventList']['primary']);


        //does not contain `secondary` entry
        $this->assertArrayNotHasKey('secondary',$parsedTransaction['eventList']);
        

        # Event flow

        //contains (correct) `start` entry
        $this->assertArrayHasKey('start',$parsedTransaction['eventFlow']);
        $this->assertArrayHasKey('account',$parsedTransaction['eventFlow']['start']);
        $this->assertEquals([
            'account' => 'rQHYSEyxX3GKK3F6sXRvdd2NHhUqaxtC6F',
            'mutation' => [
                'counterparty' => "rhub8VRN55s94qWKDv6jmDy1pUykJzF3wq",
                'currency' => "USD",
                'value' => "-0.05",
            ]
        ],$parsedTransaction['eventFlow']['start']);

        //does not contain `intermediate` entry
        $this->assertArrayNotHasKey('intermediate',$parsedTransaction['eventFlow']);

        //contains (correct) `end` entry
        $this->assertArrayHasKey('end',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => 'rPdvC6ccq8hCdPKSPJkPmyZ4Mi1oG2FFkT',
            'mutation' => [
                'currency' => "XRP",
                'value' => "0.052945",
            ]
        ],$parsedTransaction['eventFlow']['end']);

    }
}