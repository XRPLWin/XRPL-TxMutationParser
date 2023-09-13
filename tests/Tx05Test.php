<?php declare(strict_types=1);

namespace XRPLWin\XRPLTxMutatationParser\Tests;

use PHPUnit\Framework\TestCase;
use XRPLWin\XRPLTxMutatationParser\TxMutationParser;

/***
 * @see https://github.com/XRPL-Labs/TxMutationParser/blob/main/test/tx5.ts
 * @see https://hash.xrp.fans/77F965D99CDE91E5B7652EB4406B107C7BDE59A51EE14EB7549813F633296DF1/json
 */
final class Tx05Test extends TestCase
{
    public function testTrustLineAddedByOwnAccount()
    {
        $transaction = file_get_contents(__DIR__.'/fixtures/tx5.json');
        $transaction = \json_decode($transaction);
        $account = "rwietsevLFg8XSmG3bEZzFein1g8RBqWDZ";
        $TxMutationParser = new TxMutationParser($account, $transaction->result);
        $parsedTransaction = $TxMutationParser->result();
       //dd( $parsedTransaction);

        //Self (own account) must be $account
        $this->assertEquals($account,$parsedTransaction['self']['account']);
       
        # Basic info

        //Own account: one balance change
        $this->assertEquals(1,count($parsedTransaction['self']['balanceChanges']));
        
        //Transaction type SET
        $this->assertEquals(TxMutationParser::MUTATIONTYPE_SET,$parsedTransaction['type']);

        $this->assertTrue($parsedTransaction['self']['feePayer']);

        # Event list

        //does not contain `primary` entry
        $this->assertArrayNotHasKey('primary',$parsedTransaction['eventList']);

        //does not contain `secondary` entry
        $this->assertArrayNotHasKey('secondary',$parsedTransaction['eventList']);

        # Event flow
        //does not contain `start` entry
        $this->assertArrayNotHasKey('start',$parsedTransaction['eventFlow']);

        //does not contain `intermediate` entry
        $this->assertArrayNotHasKey('intermediate',$parsedTransaction['eventFlow']);

        //does not contain `end` entry
        $this->assertArrayNotHasKey('end',$parsedTransaction['eventFlow']);
    }
}