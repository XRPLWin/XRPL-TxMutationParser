<?php declare(strict_types=1);

namespace XRPLWin\XRPLTxMutatationParser\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * @see https://hash.xrp.fans/27F76F0DA13975E19F45A9B2E841545E11CAE295D8873B944A9200B1A84F1CDE/json
 * Conversion payment by using paths.
 */
final class Tx19Test extends TestCase
{
    public function testPaymentInitiator()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx19.json');
        $transaction = \json_decode($transaction);
        $account = "rNwUcrxYiTZ5cRAuEQVuQGDb7miaPRBVAd";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();
        //dd($parsedTransaction);
        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);

        # Basic info

        //Own account: one balance change
        $this->assertEquals(1,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type SET
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_SENT,$parsedTransaction['type']);

        $this->assertTrue($parsedTransaction['self']['feePayer']);

        # Event list

        //does contain `primary` entry
        $this->assertArrayHasKey('primary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'currency' => 'XRP',
            'value' => '-21.95924'
        ],$parsedTransaction['eventList']['primary']);

        //does not contain `secondary` entry
        $this->assertArrayNotHasKey('secondary',$parsedTransaction['eventList']);

        # Event flow
        //does not contain `intermediate` entry
        $this->assertArrayNotHasKey('intermediate',$parsedTransaction['eventFlow']);

        //does contain `start` entry
        $this->assertArrayHasKey('start',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => $account,
            'mutation' => [
                'currency' => "XRP",
                'value' => "-21.95924",//excluding fee
            ]
        ],$parsedTransaction['eventFlow']['start']);

        //does contain `end` entry
        $this->assertArrayHasKey('end',$parsedTransaction['eventFlow']);

        $this->assertEquals([
            'account' => 'rNFugeoj3ZN8Wv6xhuLegUBBPXKCyWLRkB',
            'mutation' => [
                'currency' => "XRP",
                'value' => "21.95924",
            ]
        ],$parsedTransaction['eventFlow']['end']);

    }

    public function testEndIncludingFeeFromRegularkeysignerPerspective()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx19.json');
        $transaction = \json_decode($transaction);
        $account = "rMgptxL3EiPTj35eWSpJDGyon8SSn4ELiH";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();


        //does contain `start` entry
        $this->assertArrayHasKey('start',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => 'rNwUcrxYiTZ5cRAuEQVuQGDb7miaPRBVAd',
            'mutation' => [
                'currency' => "XRP",
                'value' => "-21.95924",//excluding fee, this is viewed from regularkeysigners perspective
            ]
        ],$parsedTransaction['eventFlow']['start']);

        //does contain `end` entry
        $this->assertArrayHasKey('end',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => 'rNFugeoj3ZN8Wv6xhuLegUBBPXKCyWLRkB',
            'mutation' => [
                'currency' => "XRP",
                'value' => "21.95924",//excluding fee
            ]
        ],$parsedTransaction['eventFlow']['end']);
    }

    
}