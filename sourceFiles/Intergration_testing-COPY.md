# Intergration testing

This file is the source of truth for which integration tests must stay active in `sourceFiles/tests/`.
Each `##` row describes one scenario and the test method that proves it. When you add a row, Claude
(or whoever picks this up) should either find the matching test and link it via `STATUS`, or write it.

## Row template

Copy this block for every new scenario. `LAYER` and the second `STEPS` line are optional — everything
else is the minimum needed to write or verify the test without guessing.

```
## <Title — also becomes the test method name, e.g. "Ensure Foo Works" -> testEnsureFooWorks>
<1-3 sentences of business context, optional>

LAYER: Table                  <- omit if Table-level (default). Set to "Controller" only if this
                                  genuinely needs a real HTTP request/response cycle.
DATA: <fixtures / preconditions the test must set up>
WHAT_TO_TEST: <the function, method, or URL surface being exercised>
STEPS:
  1. <action> -> EXPECT: <assertion>
  2. <action> -> EXPECT: <assertion>
STATUS: ❌ missing                 <- or: ✅ covered — <path/to/Test.php>::<testMethodName>
```

Use a single `STEPS` line when there's only one action/assertion. Use a numbered list when the
scenario has multiple phases (e.g. "before the record exists" vs "after").

---

## Ensure Experiences Card can be scanned
Experiences has a public facing URL which must always be ready to scan a card.

LAYER: Table  
DATA: One experience-bearing user with a test UUID (`uuid = '123'`)  
WHAT_TO_TEST: ExperiencesTable::getPublicViewData (backs `/experiences/viewall/UUID`)  
STEPS:
1. Call getPublicViewData('123') before the user exists -> EXPECT: STATUS 404
2. Save the user with uuid '123', call getPublicViewData('123') again -> EXPECT: STATUS 200  
   STATUS: ✅ covered — sourceFiles/tests/TestCase/Model/Table/ExperiencesTableTest.php::testEnsureExperiencesCardCanBeScanned

---

## Ensure new users can sign up exactly once
Signup must succeed for a new email and reject a duplicate attempt with the same payload.

LAYER: Table
DATA: None preloaded — the test sends a raw signup payload (name, email, phone, company, password)
WHAT_TO_TEST: UsersTable::signup, UsersTable::verifyPassword
STEPS:
  1. Call signup() with a new user's data -> EXPECT: STATUS 200, and verifyPassword() confirms the saved hash matches the plaintext password
  2. Call signup() again with the same data -> EXPECT: STATUS 409 (already signed up)
STATUS: ✅ covered — sourceFiles/tests/TestCase/Model/Table/UsersTableTest.php::testSignup

---

## Ensure the quote-to-invoice-to-payment lifecycle works end-to-end
Covers a full billing flow: a quote is created, converted to an invoice with taxes, partially offset
by a credit note, then paid off.

LAYER: Table
DATA: One user, one $29.99/yr price list item, one group, and the accounting-explanations categories/types the conversion relies on (all seeded inline in the test)
WHAT_TO_TEST: QuotesTable::create, QuoteRowsTable::updateQuotes, InvoicesTable::convert, AccountingActivitiesTable::addCreditNote, AccountingActivitiesTable::addPaymentOrCredit, UsersTable::getUserInfo
STEPS:
  1. Create a quote for the group -> EXPECT: STATUS 200
  2. Add a quote row referencing the price list item -> EXPECT: row price is 29.99
  3. Convert the quote to an invoice -> EXPECT: MSG "Created Invoice with taxes", subtotal 29.99, total 34.48 (tax included)
  4. Add a $20 credit note to the invoice -> EXPECT: STATUS 200, total drops to 14.48, due_now 14.48
  5. Add a $14.48 payment -> EXPECT: due balance is 0.00
STATUS: ✅ covered — sourceFiles/tests/TestCase/Model/Table/InvoicesTableTest.php::testStart

---

## Ensure quote tax rows use effective database rules
Quote tax calculation must use the database-backed `taxes` table, round each tax row, and support
compound rows in priority order.

LAYER: Table
DATA: QC GST/PST tax rows with an older superseded GST rule
WHAT_TO_TEST: TaxesTable::getQuoteTaxes
STEPS:
  1. Calculate taxes for a $195 subtotal -> EXPECT: latest GST is used, compound PST is rounded, and tax_total is the sum of rounded rows
STATUS: ✅ covered — sourceFiles/tests/TestCase/Model/Table/TaxesTableTest.php::testGetQuoteTaxesRoundsRowsAndSupportsCompoundTaxes

---

## Ensure quote totals are recalculated from quote rows
Quote view/edit totals must be calculated from quote rows and database tax rules, then cached back
to `quotes.amount` for index/search display.

LAYER: Table
DATA: One QC client, one $100 price-list row, and QC GST/PST tax rows
WHAT_TO_TEST: QuotesTable::saveQuote, QuoteRowsTable::addPriceListRow, QuotesTable::calculateTotals
STEPS:
  1. Create a quote, add a qty 2 price-list row with a $5 discount, and calculate totals -> EXPECT: row snapshot is preserved, totals include taxes, and quotes.amount equals the calculated total
  2. Save the quote with nested existing row edits -> EXPECT: row edits are saved and totals are recalculated from the edited row
STATUS: ✅ covered — sourceFiles/tests/TestCase/Model/Table/QuotesTableTest.php::testCalculateTotalsUsesRowsTaxesAndUpdatesCachedAmount

---

## Ensure invoice conversion preserves quote row categories
Quote-to-invoice conversion must copy each quote row's accounting category when present instead of
forcing every invoice explanation into the default income category.

LAYER: Table
DATA: One quote row payload with accounting_explanations_category_id set
WHAT_TO_TEST: AccountingExplanationsTable::convert_createFromQuoteRows
STEPS:
  1. Convert the quote row into an accounting explanation -> EXPECT: accounting_explanations_category_id matches the quote row category
STATUS: ✅ covered — sourceFiles/tests/TestCase/Model/Table/AccountingExplanationsTableTest.php::testConvertCreateFromQuoteRowsPreservesQuoteRowCategory

---

## Ensure project listing and field updates work
Projects support listing by group/status and inline field edits (e.g. dragging priority), both for the
project itself and its tasks.

LAYER: Table
DATA: One project with a task, hours, and comments (see the commented-out draft below for the exact fixture shape)
WHAT_TO_TEST: ProjectsTable::index, ProjectsTable::updateField, ProjectTasksTable::updateField
STEPS:
  1. Call index() for the group -> EXPECT: the seeded project is returned
  2. Call updateField() to change the project's priority -> EXPECT: STATUS 200
  3. Call updateField() to change the project task's priority -> EXPECT: sort-update MSG confirms the change
STATUS: ❌ missing — drafted in ProjectsTableTest.php::testProjects but left commented out; only a boilerplate placeholder test runs in this class today

---

## Scaffold / placeholder tests (not real scenarios)
`InvoicesTableTest::testBoilerPlateTest` and `ProjectsTableTest::testBoilerPlateTest` are identical
copy-pasted sanity checks (save a bare user, assert it got id `1`). They don't exercise any
project-specific business logic, so they're noted here rather than given full scenario rows above —
flagging them so nobody mistakes their presence for real coverage of Invoices or Projects.
