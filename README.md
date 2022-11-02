# XRPL Transaction Mutation Parser

Parse XRPL transaction to context aware object for visual representation. It takes a XRPL transaction (outcome, meta) and an XRPL account. The XRPL account is the context from which the XPRL transaction is to be interpreted.

The account can be the sender, recipient, or an intermediate account. An intermediate account applies if e.g. there's a trade happening, touching your own offer asynchronously. You put up an offer and at some point down the road it gets (possibly partially) consumed. Alternatively, you can be an Intermediate account if you are a regular key signer or if something is rippling through your account.

The lib. then parses everything, performs all logic (include fee or not, etc.) and returns an object that is ready for use in e.g. an event list, or transaction details view, with all relevant objects parsed & calculated.

Origin: https://github.com/XRPL-Labs/TxMutationParser