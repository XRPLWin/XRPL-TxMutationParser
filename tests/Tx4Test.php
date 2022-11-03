<?php declare(strict_types=1);

namespace XRPLWin\XRPLOrderbookReader\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * @see https://github.com/XRPL-Labs/TxMutationParser/blob/main/test/tx4.ts
 * @see https://hash.xrp.fans/E788964F86299E0D5CF9ACD30D0E1DC120BBECA1AC0E10C52FED8EE8368BC9EE/json
 */
final class Tx4Test extends TestCase
{
    public function testPartialPaymentReceipient()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx4.json');
        $transaction = \json_decode($transaction);
        $account = "rPdvC6ccq8hCdPKSPJkPmyZ4Mi1oG2FFkT";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();
       

        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);
       
        # Basic info

        //Own account: one balance change
        $this->assertEquals(1,count($parsedTransaction['self']['balanceChanges']));
        
        //Transaction type RECEIVED
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_RECEIVED,$parsedTransaction['type']);

        # Event list

        //contains (correct) `primary` entry
        $this->assertArrayHasKey('primary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'currency' => 'XRP',
            'value' => '0.052945'
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