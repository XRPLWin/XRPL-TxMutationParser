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
    public function testTx2_OwnOfferConsumedPartiallyNotBySelf()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx2.json');
        $transaction = \json_decode($transaction);
        $account = "rQHYSEyxX3GKK3F6sXRvdd2NHhUqaxtC6F";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();

        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);

        # Basic info

        //Own account: two balance changes
        $this->assertEquals(2,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type TRADE
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_SENT,$parsedTransaction['type']);

        # Event list

        //contains (correct) `primary` entry
        $this->assertArrayHasKey('primary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'counterparty' => 'rhub8VRN55s94qWKDv6jmDy1pUykJzF3wq',
            'currency' => 'EUR',
            'value' => '249.99999999999999'
        ],$parsedTransaction['eventList']['primary']);

        //contains (correct) `secondary` entry
        $this->assertArrayHasKey('secondary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'currency' => 'XRP',
            'value' => '-1000'
        ],$parsedTransaction['eventList']['secondary']);

        # Event flow

        //contains (correct) `start` entry
        $this->assertArrayHasKey('start',$parsedTransaction['eventFlow']);
        $this->assertArrayHasKey('account',$parsedTransaction['eventFlow']['start']);
        $this->assertEquals([
            'account' => 'rJWSJ8b2DxpvbhJjTA3ZRiEK2xsxZNHaLP',
            'mutation' => [
                'counterparty' => "rhub8VRN55s94qWKDv6jmDy1pUykJzF3wq",
                'currency' => "EUR",
                'value' => "-9599.9999999999976",
            ]
        ],$parsedTransaction['eventFlow']['start']);

        //contains (correct) `intermediate` entry
        $this->assertArrayHasKey('intermediate',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => $account,
            'mutations' => [
                'in' => [
                    'counterparty' => "rhub8VRN55s94qWKDv6jmDy1pUykJzF3wq",
                    'currency' => "EUR",
                    'value' => "249.99999999999999",
                ],
                'out' => [
                    'currency' => "XRP",
                    'value' => "-1000",
                ]
            ]
        ],$parsedTransaction['eventFlow']['intermediate']);

        //does not containe `end` entry
        $this->assertArrayNotHasKey('end',$parsedTransaction['eventFlow']);

    }
}