<?php declare(strict_types=1);

namespace XRPLWin\XRPLTxMutatationParser\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * @see https://github.com/XRPL-Labs/TxMutationParser/blob/main/test/tx1.ts
 * @see https://hash.xrp.fans/38987B3EB8C2930E90DE756F1A6B1E7F1B6A32B79F4D0642792419D51B439698/json
 */
final class Tx14Test extends TestCase
{
    public function testAcceptNFTOffer()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx14.json');
        $transaction = \json_decode($transaction);
        $account = "rMsZProT3MjyCHP6FD9tk4A2WrwDMc6cbE";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();

        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);

        # Basic info

        //Own account: one balance change
        $this->assertEquals(1,count($parsedTransaction['self']['balanceChanges']));

        //Transaction type ACCEPT
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_ACCEPT,$parsedTransaction['type']);

        $this->assertTrue($parsedTransaction['self']['feePayer']);

        # Event list

        //contains (correct) `primary` entry
        $this->assertArrayHasKey('primary',$parsedTransaction['eventList']);
        $this->assertEquals([
            'currency' => 'XRP',
            'value' => '0.000589'
        ],$parsedTransaction['eventList']['primary']);

        //does not contain `secondary` entry
        $this->assertArrayNotHasKey('secondary',$parsedTransaction['eventList']);

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
                    'value' => "0.000564",
                ],
                'out' => null
            ]
        ],$parsedTransaction['eventFlow']['intermediate']);

        //does not contain `end` entry
        $this->assertArrayNotHasKey('end',$parsedTransaction['eventFlow']);

    }
}