<?php declare(strict_types=1);

namespace XRPLWin\XRPLTxMutatationParser\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * @see https://hash.xrp.fans/27F76F0DA13975E19F45A9B2E841545E11CAE295D8873B944A9200B1A84F1CDE/json
 * Conversion payment by using paths.
 */
final class Tx18Test extends TestCase
{
    public function testPaymentInitiator()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx18.json');
        $transaction = \json_decode($transaction);
        $account = "raEC27pkYaxN3vH8rCEotGisd5dPji38hH";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();
        //dd($parsedTransaction);
        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);

        # Basic info

        //Own account: one balance change
        $this->assertEquals(1,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type SET
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_RECEIVED,$parsedTransaction['type']);

        $this->assertFalse($parsedTransaction['self']['feePayer']);

        # Event list

        //does contain `primary` entry
        $this->assertArrayHasKey('primary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'counterparty' => 'rB3gZey7VWHYRqJHLoHDEJXJ2pEPNieKiS',
            'currency' => 'ETH',
            'value' => '-0.0000000112821192'
        ],$parsedTransaction['eventList']['primary']);

        //does not contain `secondary` entry
        $this->assertArrayNotHasKey('secondary',$parsedTransaction['eventList']);

        # Event flow
        //does not contain `start` entry
        $this->assertArrayNotHasKey('start',$parsedTransaction['eventFlow']);

        //does contain `intermediate` entry
        $this->assertArrayHasKey('intermediate',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => $account,
            'mutations' => [
                'in' => null,
                'out' => [
                    'counterparty' => 'rB3gZey7VWHYRqJHLoHDEJXJ2pEPNieKiS',
                    'currency' => "ETH",
                    'value' => "-0.0000000112821192",
                ]
            ]
        ],$parsedTransaction['eventFlow']['intermediate']);

        //does not contain `end` entry
        $this->assertArrayNotHasKey('end',$parsedTransaction['eventFlow']);

    }

    public function testPaymentFeePayer()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx18.json');
        $transaction = \json_decode($transaction);
        $account = "rKLPBY3nEhETSThEfWRaHNBH1Rxeo57UGn";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();
        //dd($parsedTransaction);
        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);

        # Basic info

        //Own account: two balance changes, fee pay and ETH currency
        $this->assertEquals(2,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type UNKNOWN
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_UNKNOWN,$parsedTransaction['type']);

        $this->assertTrue($parsedTransaction['self']['feePayer']);

        # Event list

        //does contain `primary` entry
        $this->assertArrayHasKey('primary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'counterparty' => 'rB3gZey7VWHYRqJHLoHDEJXJ2pEPNieKiS',
            'currency' => 'ETH',
            'value' => '0.0000000112596'
        ],$parsedTransaction['eventList']['primary']);

        //does not contain `secondary` entry
        $this->assertArrayNotHasKey('secondary',$parsedTransaction['eventList']);

        # Event flow
        //does contain `start` entry
        $this->assertArrayHasKey('start',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => 'raEC27pkYaxN3vH8rCEotGisd5dPji38hH',
            'mutation' => [
                'counterparty' => 'rB3gZey7VWHYRqJHLoHDEJXJ2pEPNieKiS',
                'currency' => "ETH",
                'value' => "-0.0000000112821192",
            ]
        ],$parsedTransaction['eventFlow']['start']);

        //does contain `intermediate` entry
        $this->assertArrayHasKey('intermediate',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => $account,
            'mutations' => [
                'in' => [
                    'counterparty' => 'rB3gZey7VWHYRqJHLoHDEJXJ2pEPNieKiS',
                    'currency' => "ETH",
                    'value' => "0.0000000112596",
                ],
                'out' => null
            ]
        ],$parsedTransaction['eventFlow']['intermediate']);

        //does not contain `end` entry
        $this->assertArrayNotHasKey('end',$parsedTransaction['eventFlow']);

    }

    public function testPaymentIntermediate()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx18.json');
        $transaction = \json_decode($transaction);
        $account = "rB3gZey7VWHYRqJHLoHDEJXJ2pEPNieKiS";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();
        //dd($parsedTransaction);
        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);

        # Basic info

        //Own account: two balance changes, fee pay and ETH currency
        $this->assertEquals(2,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type TRADE
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_TRADE,$parsedTransaction['type']);

        $this->assertFalse($parsedTransaction['self']['feePayer']);

        # Event list

        //does contain `primary` entry
        $this->assertArrayHasKey('primary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'counterparty' => 'raEC27pkYaxN3vH8rCEotGisd5dPji38hH',
            'currency' => 'ETH',
            'value' => '0.0000000112821192'
        ],$parsedTransaction['eventList']['primary']);

        //does contain `secondary` entry
        $this->assertArrayHasKey('secondary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'counterparty' => 'rKLPBY3nEhETSThEfWRaHNBH1Rxeo57UGn',
            'currency' => 'ETH',
            'value' => '-0.0000000112596'
        ],$parsedTransaction['eventList']['secondary']);

        # Event flow
        //does contain `start` entry
        $this->assertArrayHasKey('start',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => 'raEC27pkYaxN3vH8rCEotGisd5dPji38hH',
            'mutation' => [
                'counterparty' => 'rB3gZey7VWHYRqJHLoHDEJXJ2pEPNieKiS',
                'currency' => "ETH",
                'value' => "-0.0000000112821192",
            ]
        ],$parsedTransaction['eventFlow']['start']);

        //does contain `intermediate` entry
        $this->assertArrayHasKey('intermediate',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => $account,
            'mutations' => [
                'in' => [
                    'counterparty' => 'raEC27pkYaxN3vH8rCEotGisd5dPji38hH',
                    'currency' => "ETH",
                    'value' => "0.0000000112821192",
                ],
                'out' => [
                    'counterparty' => 'rKLPBY3nEhETSThEfWRaHNBH1Rxeo57UGn',
                    'currency' => "ETH",
                    'value' => "-0.0000000112596",
                ]
            ]
        ],$parsedTransaction['eventFlow']['intermediate']);

        //does not contain `end` entry
        $this->assertArrayNotHasKey('end',$parsedTransaction['eventFlow']);

    }
}