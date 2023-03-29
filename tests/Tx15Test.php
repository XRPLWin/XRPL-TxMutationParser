<?php declare(strict_types=1);

namespace XRPLWin\XRPLTxMutatationParser\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * @see https://github.com/XRPL-Labs/TxMutationParser/blob/main/test/tx1.ts
 * @see https://hash.xrp.fans/38987B3EB8C2930E90DE756F1A6B1E7F1B6A32B79F4D0642792419D51B439698/json
 */
final class Tx15Test extends TestCase
{
    public function testAcceptedNFTOfferTrade()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx14.json');
        $transaction = \json_decode($transaction);
        $account = "rMAHPD2Qzq4vmQ1CZNVjShsNZ9JnmMuvY3";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();

        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);

        # Basic info

        //Own account: one balance change
        $this->assertEquals(1,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type TRADE
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_TRADE,$parsedTransaction['type']);

        $this->assertFalse($parsedTransaction['self']['feePayer']);

        # Event list

        //contains (correct) `primary` entry
        $this->assertArrayHasKey('primary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'currency' => 'XRP',
            'value' => '-0.000564'
        ],$parsedTransaction['eventList']['primary']);
        
        //does not contain `secondary` entry
        $this->assertArrayNotHasKey('secondary',$parsedTransaction['eventList']);

        # Event flow

        //contains (correct) `start` entry
        $this->assertArrayHasKey('start',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => 'rMsZProT3MjyCHP6FD9tk4A2WrwDMc6cbE',
            'mutation' => [
                'currency' => 'XRP',
                'value' => '0.000564'
            ]
        ],$parsedTransaction['eventFlow']['start']);

        //contains (correct) `intermediate` entry
        $this->assertArrayHasKey('intermediate',$parsedTransaction['eventFlow']);
        $this->assertEquals([
            'account' => $account,
            'mutations' => [
                'in' => null,
                'out' => [
                    'currency' => "XRP",
                    'value' => "-0.000564",
                ]
            ]
        ],$parsedTransaction['eventFlow']['intermediate']);

        //does not contain `end` entry
        $this->assertArrayNotHasKey('end',$parsedTransaction['eventFlow']);

    }
}
