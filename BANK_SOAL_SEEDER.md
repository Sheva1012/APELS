# BANK SOAL — ENGLISH FOR ACCOUNTING
**Sumber:** SET_A.docx, SET_B.docx, SET_C.docx
**Total:** 60 soal (3 modul × 20 soal)
**Tujuan file:** sumber data tunggal (single source of truth) untuk database seeder.

---

## 1. SKEMA DATA

| Field | Tipe | Enum / Format | Keterangan |
|---|---|---|---|
| `code` | string | `A-01` … `C-20` | Unique key, aman untuk `updateOrCreate` |
| `module_code` | char(1) | `A`, `B`, `C` | Kode modul |
| `module_title` | string | — | Judul modul |
| `level` | enum | `basic`, `intermediate`, `advanced` | 7 / 7 / 6 soal per modul |
| `number` | int | 1–20 | Nomor urut dalam modul |
| `type` | enum | `vocabulary`, `grammar` | Kategori kompetensi |
| `question` | text | `________` = blank | Placeholder isian |
| `options` | json | key `A`–`D` | 4 opsi |
| `answer_key` | char(1) | `A`–`D` | Kunci jawaban |
| `feedback` | text | — | Umpan balik AI |

**Pemetaan level ↔ nomor soal (identik di ketiga modul):**

| Level | Nomor Soal | Jumlah |
|---|---|---|
| `basic` | 1 – 7 | 7 |
| `intermediate` | 8 – 14 | 7 |
| `advanced` | 15 – 20 | 6 |

**Judul modul:**

| Kode | Judul |
|---|---|
| A | Financial Position & Transaction Reporting |
| B | Income Statement & Performance Analysis |
| C | Cash Flow, Auditing & Taxation |

---

## 2. MODUL A — FINANCIAL POSITION & TRANSACTION REPORTING

### 2.1 Level: BASIC (Soal 1–7)

#### A-01 · vocabulary
**Q:** The financial document that shows a company's financial position by listing its assets, liabilities, and owners' equity at a specific point in time is called the...
- A. Income Statement
- B. Balance Sheet
- C. Cash Flow Statement
- D. Trial Balance

**KEY:** B
**FEEDBACK:** The Balance Sheet presents the financial condition at a given date based on the accounting equation: Assets = Liabilities + Equity.

#### A-02 · grammar
**Q:** Before the annual report is published, all material transactions ________ in the general ledger by the accounting staff.
- A. is posted
- B. are posted
- C. posts
- D. posting

**KEY:** B
**FEEDBACK:** Since the subject 'all material transactions' is plural and receives the action, we use the plural passive voice (are + Past Participle).

#### A-03 · vocabulary
**Q:** Obligations that a company expects to pay within the next 12 months are classified as...
- A. Long-term liabilities
- B. Current liabilities
- C. Intangible assets
- D. Fixed assets

**KEY:** B
**FEEDBACK:** Current liabilities are obligations due within one year or one operating cycle.

#### A-04 · grammar
**Q:** Cash and accounts receivable ________ categorized as current assets on the balance sheet.
- A. is
- B. are
- C. was
- D. being

**KEY:** B
**FEEDBACK:** Compound subjects ('Cash and accounts receivable') require a plural verb.

#### A-05 · vocabulary
**Q:** The amounts owed to a company by its customers for goods sold or services performed on credit are called...
- A. Accounts Payable
- B. Accounts Receivable
- C. Retained Earnings
- D. Unearned Revenues

**KEY:** B
**FEEDBACK:** Accounts Receivable represents credit extended to customers that is yet to be collected.

#### A-06 · grammar
**Q:** The company ________ its accounting records every day to maintain transparency.
- A. update
- B. updates
- C. updating
- D. have updated

**KEY:** B
**FEEDBACK:** Singular subject 'The company' requires a singular verb in Simple Present tense ('updates').

#### A-07 · vocabulary
**Q:** The initial book where economic transactions are recorded in chronological order before being transferred to ledgers is the...
- A. Worksheet
- B. Balance sheet
- C. Journal
- D. Income statement

**KEY:** C
**FEEDBACK:** A journal is the book of original entry where transactions are entered chronologically.

### 2.2 Level: INTERMEDIATE (Soal 8–14)

#### A-08 · vocabulary
**Q:** When an accountant records a transaction that reduces the value of a tangible fixed asset over its estimated useful life, this expense is called...
- A. Amortization
- B. Depreciation
- C. Depletion
- D. Devaluation

**KEY:** B
**FEEDBACK:** Depreciation is the systematic cost allocation for tangible assets. Amortization is for intangible assets.

#### A-09 · grammar
**Q:** The company's liquidity decreased significantly last quarter ________ a sudden increase in unpaid accounts receivable.
- A. because
- B. due to
- C. although
- D. in order to

**KEY:** B
**FEEDBACK:** 'Due to' acts as a preposition followed by a noun phrase. Use 'because' for clauses with verbs.

#### A-10 · vocabulary
**Q:** A temporary account that shows the distribution of a corporation's net income to its shareholders is called...
- A. Retained Earnings
- B. Dividends
- C. Common Stock
- D. Treasury Stock

**KEY:** B
**FEEDBACK:** Dividends represent the distribution of corporate profits to stockholders.

#### A-11 · grammar
**Q:** While the accountants ________ the general ledger adjustments, the system suddenly crashed.
- A. are making
- B. were making
- C. made
- D. have made

**KEY:** B
**FEEDBACK:** Use Past Continuous ('were making') for an ongoing past action interrupted by another event.

#### A-12 · vocabulary
**Q:** Revenues received in advance that are not yet earned are recorded as a liability under...
- A. Accrued Revenue
- B. Unearned Revenue
- C. Prepaid Expense
- D. Accounts Payable

**KEY:** B
**FEEDBACK:** Unearned revenue represents cash received before goods or services are delivered, creating a performance obligation.

#### A-13 · grammar
**Q:** Senior accountants prefer using automated software ________ it minimizes human mathematical errors.
- A. although
- B. because
- C. despite
- D. therefore

**KEY:** B
**FEEDBACK:** 'Because' introduces a subordinate clause explaining the reason for the preference.

#### A-14 · vocabulary
**Q:** The excess of total assets over total liabilities for a proprietary business entity is defined as...
- A. Gross Profit
- B. Net Income
- C. Owner's Equity
- D. Working Capital

**KEY:** C
**FEEDBACK:** Owner's Equity represents the residual interest in the assets after deducting all liabilities.

### 2.3 Level: ADVANCED (Soal 15–20)

#### A-15 · vocabulary
**Q:** If a firm has a current ratio of 0.75, an expert financial analyst would immediately conclude that the business might have trouble...
- A. paying off its long-term corporate bonds
- B. meeting its short-term debt obligations
- C. maximizing its net profit margin
- D. depreciating its fixed assets accurately

**KEY:** B
**FEEDBACK:** A current ratio below 1.0 indicates that current assets are insufficient to cover current liabilities, showing poor short-term liquidity.

#### A-16 · grammar
**Q:** If the internal control system ________ stricter guidelines last semester, the unauthorized cash disbursement could have been prevented.
- A. enforced
- B. has enforced
- C. had enforced
- D. would enforce

**KEY:** C
**FEEDBACK:** This is a Conditional Type 3 sentence (unreal past). The if-clause requires the Past Perfect tense (had + Past Participle).

#### A-17 · vocabulary
**Q:** The accounting convention stating that a business transaction must be recorded based on objective evidence rather than subjective opinion is the...
- A. Going Concern Principle
- B. Objectivity Principle
- C. Materiality Concept
- D. Conservatism Principle

**KEY:** B
**FEEDBACK:** The Objectivity Principle requires accounting data to be bias-free, verifiable, and backed by evidence.

#### A-18 · grammar
**Q:** Had the corporation ________ its capitalization structure earlier, it would not have faced technical insolvency.
- A. restructure
- B. restructuring
- C. restructured
- D. restructures

**KEY:** C
**FEEDBACK:** This is an inverted Conditional Type 3 structure. 'Had' functions as the helping verb, requiring a Past Participle verb ('restructured').

#### A-19 · vocabulary
**Q:** A financial metric calculated by subtracting current liabilities from current assets, indicating short-term operational capital available, is...
- A. Debt Ratio
- B. Return on Assets
- C. Working Capital
- D. Asset Turnover

**KEY:** C
**FEEDBACK:** Working Capital represents the liquid capital available for daily operations (Current Assets - Current Liabilities).

#### A-20 · grammar
**Q:** It is vital that the management ________ the revaluation of fixed assets prior to the tax audit.
- A. approves
- B. approve
- C. approved
- D. is approving

**KEY:** B
**FEEDBACK:** The subjunctive mood formula is used after words of necessity ('It is vital that...'). It requires the base form of the verb ('approve').

---

## 3. MODUL B — INCOME STATEMENT & PERFORMANCE ANALYSIS

### 3.1 Level: BASIC (Soal 1–7)

#### B-01 · vocabulary
**Q:** Gross profit is calculated by deducting the ________ from the company's total net sales revenue.
- A. Operating Expenses
- B. Cost of Goods Sold (COGS)
- C. Income Tax Expense
- D. Interest Expense

**KEY:** B
**FEEDBACK:** Gross Profit measures the direct profit margin from manufacturing or core trade, computed as Net Sales - COGS.

#### B-02 · grammar
**Q:** The quarterly financial statements ________ currently being reviewed by the board of directors.
- A. is
- B. are
- C. was
- D. am

**KEY:** B
**FEEDBACK:** The plural subject 'financial statements' paired with 'currently' indicates a Present Continuous Passive construction.

#### B-03 · vocabulary
**Q:** Outflows or other using up of assets or incurrences of liabilities during a period from delivering or producing goods are called...
- A. Revenues
- B. Expenses
- C. Assets
- D. Dividends

**KEY:** B
**FEEDBACK:** Expenses are the costs incurred in the process of generating corporate revenues.

#### B-04 · grammar
**Q:** Last year, PT Nusantara ________ a double-digit growth in its operational revenue.
- A. achieves
- B. achieved
- C. achieving
- D. has achieved

**KEY:** B
**FEEDBACK:** The time marker 'Last year' specifies a completed past action, requiring the Simple Past tense ('achieved').

#### B-05 · vocabulary
**Q:** The monetary expression of the amount of resources generated by a business entity through selling goods or providing services is...
- A. Investment
- B. Revenue
- C. Capital
- D. Equity

**KEY:** B
**FEEDBACK:** Revenue is the total inflow of economic benefits arising from core business operations.

#### B-06 · grammar
**Q:** Marketing expenses usually ________ along with the increase in advertising campaigns.
- A. rise
- B. rises
- C. rising
- D. has risen

**KEY:** A
**FEEDBACK:** Plural subject 'Marketing expenses' requires a base plural verb ('rise') in general facts reporting.

#### B-07 · vocabulary
**Q:** The final line on the income statement, representing the net earnings remaining after all expenses are deducted, is...
- A. Operating Income
- B. Gross Profit
- C. Net Income
- D. EBIT

**KEY:** C
**FEEDBACK:** Net income is the definitive net earnings remaining after subtracting all operational and non-operational costs.

### 3.2 Level: INTERMEDIATE (Soal 8–14)

#### B-08 · vocabulary
**Q:** Under the FIFO inventory valuation method, an accountant assumes that the items purchased ________ are the first ones to be sold.
- A. last
- B. first
- C. at the highest cost
- D. at the lowest cost

**KEY:** B
**FEEDBACK:** FIFO stands for First-In, First-Out, meaning older inventory units are accounted for as sold first.

#### B-09 · grammar
**Q:** The corporate management decided to cut travel budgets ________ maximize the remaining net income for the fiscal year.
- A. because of
- B. so that
- C. in order to
- D. due to

**KEY:** C
**FEEDBACK:** 'In order to' expresses purpose and must be followed directly by a base verb infinitive ('maximize').

#### B-10 · vocabulary
**Q:** Costs that remain constant in total dollar amount regardless of changes in the level of production activity are called...
- A. Variable costs
- B. Fixed costs
- C. Direct costs
- D. Marginal costs

**KEY:** B
**FEEDBACK:** Fixed costs do not fluctuate in total with changes in short-term volume production levels (e.g., rent).

#### B-11 · grammar
**Q:** Our sales revenue increased by 15%, ________ our utility expenses dropped by 5%.
- A. because
- B. while
- C. so that
- D. therefore

**KEY:** B
**FEEDBACK:** 'While' acts as a coordinating conjunction to contrast two simultaneous trends.

#### B-12 · vocabulary
**Q:** An accounting matching concept states that expenses incurred to generate revenue must be recognized in the...
- A. period the cash is paid out
- B. same period as the related revenue
- C. following fiscal year
- D. period the contract is signed

**KEY:** B
**FEEDBACK:** The Matching Principle dictates that expenses must follow the revenues they helped generate in the same period.

#### B-13 · grammar
**Q:** The new accountant suggests that the cost of goods sold ________ recalculated using the weighted-average method.
- A. is
- B. be
- C. was
- D. being

**KEY:** B
**FEEDBACK:** The verb 'suggests' demands the subjunctive mood in the noun clause, hence the use of the base form 'be'.

#### B-14 · vocabulary
**Q:** The profit margin earned after deducting both cost of goods sold and regular operating expenses from net sales is...
- A. Net Income
- B. Gross Income
- C. Operating Income
- D. Retained Profit

**KEY:** C
**FEEDBACK:** Operating income evaluates standard earnings from core operations, excluding tax and interest burdens.

### 3.3 Level: ADVANCED (Soal 15–20)

> Catatan: pada dokumen sumber, urutan soal 15–20 tercetak acak (15, 16, 20, 18, 19, 17). Di sini sudah **diurutkan ulang** sesuai nomor aslinya. Isi soal tidak diubah.

#### B-15 · vocabulary
**Q:** A significant discrepancy between the physical inventory count and the general ledger records often indicates a high risk of...
- A. asset appreciation
- B. inventory shrinkage or fraud
- C. deferred tax liability
- D. accurate revenue recognition

**KEY:** B
**FEEDBACK:** Unexplained missing stock points to inventory shrinkage, which is often tied to internal control failures, theft, or fraud.

#### B-16 · grammar
**Q:** If the corporation had not optimized its operational expenses, its net profit margin ________ so high today.
- A. will not be
- B. would not be
- C. had not been
- D. could not have been

**KEY:** B
**FEEDBACK:** This is a mixed conditional. The past condition ('had not optimized') affects the current state, requiring 'would not be'.

#### B-17 · grammar
**Q:** Never ________ seen such a drastic inflation adjustment on the company's financial records.
- A. the auditor had
- B. had the auditor
- C. did the auditor
- D. the auditor has

**KEY:** B
**FEEDBACK:** Negative adverbs like 'Never' at the front of a sentence trigger subject-auxiliary inversion ('had the auditor').

#### B-18 · grammar
**Q:** Under IFRS framework, revenue ________ unless the performance obligations are fully satisfied.
- A. cannot recognize
- B. cannot be recognized
- C. should not recognize
- D. is not recognizing

**KEY:** B
**FEEDBACK:** The modal passive structure (modal + be + Past Participle) is required since revenue cannot perform the recognition itself.

#### B-19 · vocabulary
**Q:** When revenues are recognized when earned and expenses are recognized when incurred, regardless of cash movement, it is called...
- A. Cash basis accounting
- B. Accrual basis accounting
- C. Deferral accounting
- D. Inflation accounting

**KEY:** B
**FEEDBACK:** Accrual accounting records financial transactions based on economic events rather than actual cash flows.

#### B-20 · vocabulary
**Q:** An analytical metric evaluating earnings relative to equity capital invested by shareholders is named...
- A. Return on Equity (ROE)
- B. Debt-to-Equity Ratio
- C. Price-Earnings Ratio
- D. Asset Turnover

**KEY:** A
**FEEDBACK:** ROE measures profit generation efficiency per unit of owner capital invested (Net Income / Shareholders' Equity).

---

## 4. MODUL C — CASH FLOW, AUDITING & TAXATION

### 4.1 Level: BASIC (Soal 1–7)

#### C-01 · vocabulary
**Q:** Cash inflows and outflows directly related to borrowing money or issuing corporate shares are classified under...
- A. Operating Activities
- B. Investing Activities
- C. Financing Activities
- D. Adjusting Activities

**KEY:** C
**FEEDBACK:** Financing activities deal with capital procurement from equity issues or long-term debt modifications.

#### C-02 · grammar
**Q:** Yesterday, the ledger accounts ________ balanced perfectly by the junior accounting team before 5 PM.
- A. are
- B. were
- C. was
- D. been

**KEY:** B
**FEEDBACK:** The past plural constraint ('Yesterday' + 'accounts') requires the past plural passive form 'were'.

#### C-03 · vocabulary
**Q:** An independent examination of the financial statements of an entity to express an opinion is a/an...
- A. Taxation
- B. Auditing
- C. Bookkeeping
- D. Budgeting

**KEY:** B
**FEEDBACK:** An audit verifies whether financial statements present a true and fair view of corporate operations.

#### C-04 · grammar
**Q:** The tax collector ________ the corporate financial ledger right now.
- A. examine
- B. is examining
- C. examined
- D. has examined

**KEY:** B
**FEEDBACK:** 'Right now' signals a Present Continuous tense action for a singular subject ('is examining').

#### C-05 · vocabulary
**Q:** The financial statement that reports the cash receipts and cash payments of a business over a period is the...
- A. Income Statement
- B. Statement of Cash Flows
- C. Balance Sheet
- D. Retained Earnings Statement

**KEY:** B
**FEEDBACK:** The Statement of Cash Flows categorizes cash changes into operating, investing, and financing groups.

#### C-06 · grammar
**Q:** The internal auditor ________ completed the preliminary financial evaluation checklist.
- A. have
- B. has
- C. having
- D. is

**KEY:** B
**FEEDBACK:** Singular subject 'The internal auditor' requires 'has' for Present Perfect constructions.

#### C-07 · vocabulary
**Q:** Mandatory financial contributions imposed by the government on corporate income or transactions are called...
- A. Subsidies
- B. Taxes
- C. Dividends
- D. Fines

**KEY:** B
**FEEDBACK:** Taxes are compulsory levies enforced by state authorities on corporate profits or activities.

### 4.2 Level: INTERMEDIATE (Soal 8–14)

#### C-08 · vocabulary
**Q:** When a firm sells services on account, the transaction increases revenue and generates a financial claim called...
- A. Accounts Payable
- B. Accounts Receivable
- C. Retained Earnings
- D. Accrued Expenses

**KEY:** B
**FEEDBACK:** Accounts Receivable tracks open account balances due from buyers for completed deals.

#### C-09 · grammar
**Q:** The company expanded its production line ________ its net profit dropped slightly compared to the previous month.
- A. because
- B. even though
- C. therefore
- D. due to

**KEY:** B
**FEEDBACK:** 'Even though' serves as a concession conjunction to contrast expansion against profit reduction.

#### C-10 · vocabulary
**Q:** Cash flows resulting from purchasing or selling property, plant, and equipment (PPE) are classified as...
- A. Operating Activities
- B. Investing Activities
- C. Financing Activities
- D. Liquid Activities

**KEY:** B
**FEEDBACK:** Investing activities encompass the acquisition and disposal of long-term productive assets and non-cash investments.

#### C-11 · grammar
**Q:** The company must submit its annual tax return forms ________ April 30th to avoid statutory late fees.
- A. at
- B. on
- C. before
- D. in

**KEY:** C
**FEEDBACK:** 'Before' designates a hard time constraint deadline to prevent penalties.

#### C-12 · vocabulary
**Q:** A document prepared to verify and bring the cash balance per a company's records into agreement with the bank statement balance is a...
- A. Petty Cash Log
- B. Bank Reconciliation
- C. Trial Balance
- D. General Ledger

**KEY:** B
**FEEDBACK:** Bank reconciliation rectifies timing differences between the internal ledger cash accounts and external bank statements.

#### C-13 · grammar
**Q:** The tax regulations require that all transaction receipts ________ kept for at least five years.
- A. are
- B. be
- C. being
- D. was

**KEY:** B
**FEEDBACK:** Mandatory commands like 'require that' invoke the subjunctive form, demanding the base verb 'be' in passive construction.

#### C-14 · vocabulary
**Q:** The risk that an auditor will mistakenly issue an unmodified audit opinion on financial statements that contain material misstatements is...
- A. Inherent Risk
- B. Control Risk
- C. Audit Risk
- D. Detection Risk

**KEY:** C
**FEEDBACK:** Audit Risk is the combination of misstatement occurrence risks and an auditor's failure to capture them.

### 4.3 Level: ADVANCED (Soal 15–20)

#### C-15 · vocabulary
**Q:** During an external audit, if an auditor finds that the financial records are free from material misstatements, the company receives a/an...
- A. Adverse opinion
- B. Qualified opinion
- C. Unqualified opinion
- D. Disclaimer of opinion

**KEY:** C
**FEEDBACK:** An Unqualified Opinion indicates clean compliance with GAAP/IFRS standards without critical errors.

#### C-16 · grammar
**Q:** The chief auditor ________ the fraud scheme if he had cross-checked the bank reconciliation statements with the physical cash logs.
- A. would discover
- B. could have discovered
- C. had discovered
- D. discovered

**KEY:** B
**FEEDBACK:** Conditional Type 3 main clause format utilizes could/would have + Past Participle to denote past capability.

#### C-17 · vocabulary
**Q:** An intentional manipulation of tax accounting methods to illegally reduce tax liability is known as...
- A. Tax avoidance
- B. Tax evasion
- C. Tax deferral
- D. Tax planning

**KEY:** B
**FEEDBACK:** Tax evasion is the illegal practice of misrepresenting profits to evade fiscal obligations. Avoidance operates within legal gaps.

#### C-18 · grammar
**Q:** Rarely ________ such high discrepancies between the direct and indirect cash flow calculations.
- A. we encounter
- B. do we encounter
- C. have we encountering
- D. we do encounter

**KEY:** B
**FEEDBACK:** Negative adverbs like 'Rarely' necessitate subject-auxiliary inversion in present statement descriptions ('do we encounter').

#### C-19 · vocabulary
**Q:** Which audit risk component represents the susceptibility of an account balance to a material misstatement assuming no related internal controls?
- A. Control Risk
- B. Inherent Risk
- C. Detection Risk
- D. Operational Risk

**KEY:** B
**FEEDBACK:** Inherent Risk represents the natural risk level native to an environment or transaction category before controls are applied.

#### C-20 · grammar
**Q:** The CFO requested that the corporate financial statements ________ with tax compliance disclosures before final submission.
- A. are appended
- B. be appended
- C. will be appended
- D. should append

**KEY:** B
**FEEDBACK:** The matrix verb 'requested' triggers a mandative subjunctive structure, matching the base form passive construction 'be appended'.

---

## 5. LAMPIRAN: PAYLOAD JSON SIAP-SEEDER

Blok di bawah ini adalah representasi mesin dari seluruh 60 soal di atas.
Simpan sebagai `database/data/questions.json`, lalu di seeder cukup:

```php
$items = json_decode(file_get_contents(database_path('data/questions.json')), true);
foreach ($items as $item) {
    Question::updateOrCreate(
        ['code' => $item['code']],
        [
            'module_code'  => $item['module_code'],
            'module_title' => $item['module_title'],
            'level'        => $item['level'],
            'number'       => $item['number'],
            'type'         => $item['type'],
            'question'     => $item['question'],
            'options'      => $item['options'],   // cast: 'array'
            'answer_key'   => $item['answer_key'],
            'feedback'     => $item['feedback'],
        ]
    );
}
```

```json
[
{"code":"A-01","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"basic","number":1,"type":"vocabulary","question":"The financial document that shows a company's financial position by listing its assets, liabilities, and owners' equity at a specific point in time is called the...","options":{"A":"Income Statement","B":"Balance Sheet","C":"Cash Flow Statement","D":"Trial Balance"},"answer_key":"B","feedback":"The Balance Sheet presents the financial condition at a given date based on the accounting equation: Assets = Liabilities + Equity."},
{"code":"A-02","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"basic","number":2,"type":"grammar","question":"Before the annual report is published, all material transactions ________ in the general ledger by the accounting staff.","options":{"A":"is posted","B":"are posted","C":"posts","D":"posting"},"answer_key":"B","feedback":"Since the subject 'all material transactions' is plural and receives the action, we use the plural passive voice (are + Past Participle)."},
{"code":"A-03","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"basic","number":3,"type":"vocabulary","question":"Obligations that a company expects to pay within the next 12 months are classified as...","options":{"A":"Long-term liabilities","B":"Current liabilities","C":"Intangible assets","D":"Fixed assets"},"answer_key":"B","feedback":"Current liabilities are obligations due within one year or one operating cycle."},
{"code":"A-04","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"basic","number":4,"type":"grammar","question":"Cash and accounts receivable ________ categorized as current assets on the balance sheet.","options":{"A":"is","B":"are","C":"was","D":"being"},"answer_key":"B","feedback":"Compound subjects ('Cash and accounts receivable') require a plural verb."},
{"code":"A-05","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"basic","number":5,"type":"vocabulary","question":"The amounts owed to a company by its customers for goods sold or services performed on credit are called...","options":{"A":"Accounts Payable","B":"Accounts Receivable","C":"Retained Earnings","D":"Unearned Revenues"},"answer_key":"B","feedback":"Accounts Receivable represents credit extended to customers that is yet to be collected."},
{"code":"A-06","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"basic","number":6,"type":"grammar","question":"The company ________ its accounting records every day to maintain transparency.","options":{"A":"update","B":"updates","C":"updating","D":"have updated"},"answer_key":"B","feedback":"Singular subject 'The company' requires a singular verb in Simple Present tense ('updates')."},
{"code":"A-07","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"basic","number":7,"type":"vocabulary","question":"The initial book where economic transactions are recorded in chronological order before being transferred to ledgers is the...","options":{"A":"Worksheet","B":"Balance sheet","C":"Journal","D":"Income statement"},"answer_key":"C","feedback":"A journal is the book of original entry where transactions are entered chronologically."},
{"code":"A-08","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"intermediate","number":8,"type":"vocabulary","question":"When an accountant records a transaction that reduces the value of a tangible fixed asset over its estimated useful life, this expense is called...","options":{"A":"Amortization","B":"Depreciation","C":"Depletion","D":"Devaluation"},"answer_key":"B","feedback":"Depreciation is the systematic cost allocation for tangible assets. Amortization is for intangible assets."},
{"code":"A-09","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"intermediate","number":9,"type":"grammar","question":"The company's liquidity decreased significantly last quarter ________ a sudden increase in unpaid accounts receivable.","options":{"A":"because","B":"due to","C":"although","D":"in order to"},"answer_key":"B","feedback":"'Due to' acts as a preposition followed by a noun phrase. Use 'because' for clauses with verbs."},
{"code":"A-10","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"intermediate","number":10,"type":"vocabulary","question":"A temporary account that shows the distribution of a corporation's net income to its shareholders is called...","options":{"A":"Retained Earnings","B":"Dividends","C":"Common Stock","D":"Treasury Stock"},"answer_key":"B","feedback":"Dividends represent the distribution of corporate profits to stockholders."},
{"code":"A-11","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"intermediate","number":11,"type":"grammar","question":"While the accountants ________ the general ledger adjustments, the system suddenly crashed.","options":{"A":"are making","B":"were making","C":"made","D":"have made"},"answer_key":"B","feedback":"Use Past Continuous ('were making') for an ongoing past action interrupted by another event."},
{"code":"A-12","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"intermediate","number":12,"type":"vocabulary","question":"Revenues received in advance that are not yet earned are recorded as a liability under...","options":{"A":"Accrued Revenue","B":"Unearned Revenue","C":"Prepaid Expense","D":"Accounts Payable"},"answer_key":"B","feedback":"Unearned revenue represents cash received before goods or services are delivered, creating a performance obligation."},
{"code":"A-13","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"intermediate","number":13,"type":"grammar","question":"Senior accountants prefer using automated software ________ it minimizes human mathematical errors.","options":{"A":"although","B":"because","C":"despite","D":"therefore"},"answer_key":"B","feedback":"'Because' introduces a subordinate clause explaining the reason for the preference."},
{"code":"A-14","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"intermediate","number":14,"type":"vocabulary","question":"The excess of total assets over total liabilities for a proprietary business entity is defined as...","options":{"A":"Gross Profit","B":"Net Income","C":"Owner's Equity","D":"Working Capital"},"answer_key":"C","feedback":"Owner's Equity represents the residual interest in the assets after deducting all liabilities."},
{"code":"A-15","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"advanced","number":15,"type":"vocabulary","question":"If a firm has a current ratio of 0.75, an expert financial analyst would immediately conclude that the business might have trouble...","options":{"A":"paying off its long-term corporate bonds","B":"meeting its short-term debt obligations","C":"maximizing its net profit margin","D":"depreciating its fixed assets accurately"},"answer_key":"B","feedback":"A current ratio below 1.0 indicates that current assets are insufficient to cover current liabilities, showing poor short-term liquidity."},
{"code":"A-16","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"advanced","number":16,"type":"grammar","question":"If the internal control system ________ stricter guidelines last semester, the unauthorized cash disbursement could have been prevented.","options":{"A":"enforced","B":"has enforced","C":"had enforced","D":"would enforce"},"answer_key":"C","feedback":"This is a Conditional Type 3 sentence (unreal past). The if-clause requires the Past Perfect tense (had + Past Participle)."},
{"code":"A-17","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"advanced","number":17,"type":"vocabulary","question":"The accounting convention stating that a business transaction must be recorded based on objective evidence rather than subjective opinion is the...","options":{"A":"Going Concern Principle","B":"Objectivity Principle","C":"Materiality Concept","D":"Conservatism Principle"},"answer_key":"B","feedback":"The Objectivity Principle requires accounting data to be bias-free, verifiable, and backed by evidence."},
{"code":"A-18","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"advanced","number":18,"type":"grammar","question":"Had the corporation ________ its capitalization structure earlier, it would not have faced technical insolvency.","options":{"A":"restructure","B":"restructuring","C":"restructured","D":"restructures"},"answer_key":"C","feedback":"This is an inverted Conditional Type 3 structure. 'Had' functions as the helping verb, requiring a Past Participle verb ('restructured')."},
{"code":"A-19","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"advanced","number":19,"type":"vocabulary","question":"A financial metric calculated by subtracting current liabilities from current assets, indicating short-term operational capital available, is...","options":{"A":"Debt Ratio","B":"Return on Assets","C":"Working Capital","D":"Asset Turnover"},"answer_key":"C","feedback":"Working Capital represents the liquid capital available for daily operations (Current Assets - Current Liabilities)."},
{"code":"A-20","module_code":"A","module_title":"Financial Position & Transaction Reporting","level":"advanced","number":20,"type":"grammar","question":"It is vital that the management ________ the revaluation of fixed assets prior to the tax audit.","options":{"A":"approves","B":"approve","C":"approved","D":"is approving"},"answer_key":"B","feedback":"The subjunctive mood formula is used after words of necessity ('It is vital that...'). It requires the base form of the verb ('approve')."},
{"code":"B-01","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"basic","number":1,"type":"vocabulary","question":"Gross profit is calculated by deducting the ________ from the company's total net sales revenue.","options":{"A":"Operating Expenses","B":"Cost of Goods Sold (COGS)","C":"Income Tax Expense","D":"Interest Expense"},"answer_key":"B","feedback":"Gross Profit measures the direct profit margin from manufacturing or core trade, computed as Net Sales - COGS."},
{"code":"B-02","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"basic","number":2,"type":"grammar","question":"The quarterly financial statements ________ currently being reviewed by the board of directors.","options":{"A":"is","B":"are","C":"was","D":"am"},"answer_key":"B","feedback":"The plural subject 'financial statements' paired with 'currently' indicates a Present Continuous Passive construction."},
{"code":"B-03","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"basic","number":3,"type":"vocabulary","question":"Outflows or other using up of assets or incurrences of liabilities during a period from delivering or producing goods are called...","options":{"A":"Revenues","B":"Expenses","C":"Assets","D":"Dividends"},"answer_key":"B","feedback":"Expenses are the costs incurred in the process of generating corporate revenues."},
{"code":"B-04","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"basic","number":4,"type":"grammar","question":"Last year, PT Nusantara ________ a double-digit growth in its operational revenue.","options":{"A":"achieves","B":"achieved","C":"achieving","D":"has achieved"},"answer_key":"B","feedback":"The time marker 'Last year' specifies a completed past action, requiring the Simple Past tense ('achieved')."},
{"code":"B-05","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"basic","number":5,"type":"vocabulary","question":"The monetary expression of the amount of resources generated by a business entity through selling goods or providing services is...","options":{"A":"Investment","B":"Revenue","C":"Capital","D":"Equity"},"answer_key":"B","feedback":"Revenue is the total inflow of economic benefits arising from core business operations."},
{"code":"B-06","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"basic","number":6,"type":"grammar","question":"Marketing expenses usually ________ along with the increase in advertising campaigns.","options":{"A":"rise","B":"rises","C":"rising","D":"has risen"},"answer_key":"A","feedback":"Plural subject 'Marketing expenses' requires a base plural verb ('rise') in general facts reporting."},
{"code":"B-07","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"basic","number":7,"type":"vocabulary","question":"The final line on the income statement, representing the net earnings remaining after all expenses are deducted, is...","options":{"A":"Operating Income","B":"Gross Profit","C":"Net Income","D":"EBIT"},"answer_key":"C","feedback":"Net income is the definitive net earnings remaining after subtracting all operational and non-operational costs."},
{"code":"B-08","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"intermediate","number":8,"type":"vocabulary","question":"Under the FIFO inventory valuation method, an accountant assumes that the items purchased ________ are the first ones to be sold.","options":{"A":"last","B":"first","C":"at the highest cost","D":"at the lowest cost"},"answer_key":"B","feedback":"FIFO stands for First-In, First-Out, meaning older inventory units are accounted for as sold first."},
{"code":"B-09","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"intermediate","number":9,"type":"grammar","question":"The corporate management decided to cut travel budgets ________ maximize the remaining net income for the fiscal year.","options":{"A":"because of","B":"so that","C":"in order to","D":"due to"},"answer_key":"C","feedback":"'In order to' expresses purpose and must be followed directly by a base verb infinitive ('maximize')."},
{"code":"B-10","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"intermediate","number":10,"type":"vocabulary","question":"Costs that remain constant in total dollar amount regardless of changes in the level of production activity are called...","options":{"A":"Variable costs","B":"Fixed costs","C":"Direct costs","D":"Marginal costs"},"answer_key":"B","feedback":"Fixed costs do not fluctuate in total with changes in short-term volume production levels (e.g., rent)."},
{"code":"B-11","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"intermediate","number":11,"type":"grammar","question":"Our sales revenue increased by 15%, ________ our utility expenses dropped by 5%.","options":{"A":"because","B":"while","C":"so that","D":"therefore"},"answer_key":"B","feedback":"'While' acts as a coordinating conjunction to contrast two simultaneous trends."},
{"code":"B-12","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"intermediate","number":12,"type":"vocabulary","question":"An accounting matching concept states that expenses incurred to generate revenue must be recognized in the...","options":{"A":"period the cash is paid out","B":"same period as the related revenue","C":"following fiscal year","D":"period the contract is signed"},"answer_key":"B","feedback":"The Matching Principle dictates that expenses must follow the revenues they helped generate in the same period."},
{"code":"B-13","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"intermediate","number":13,"type":"grammar","question":"The new accountant suggests that the cost of goods sold ________ recalculated using the weighted-average method.","options":{"A":"is","B":"be","C":"was","D":"being"},"answer_key":"B","feedback":"The verb 'suggests' demands the subjunctive mood in the noun clause, hence the use of the base form 'be'."},
{"code":"B-14","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"intermediate","number":14,"type":"vocabulary","question":"The profit margin earned after deducting both cost of goods sold and regular operating expenses from net sales is...","options":{"A":"Net Income","B":"Gross Income","C":"Operating Income","D":"Retained Profit"},"answer_key":"C","feedback":"Operating income evaluates standard earnings from core operations, excluding tax and interest burdens."},
{"code":"B-15","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"advanced","number":15,"type":"vocabulary","question":"A significant discrepancy between the physical inventory count and the general ledger records often indicates a high risk of...","options":{"A":"asset appreciation","B":"inventory shrinkage or fraud","C":"deferred tax liability","D":"accurate revenue recognition"},"answer_key":"B","feedback":"Unexplained missing stock points to inventory shrinkage, which is often tied to internal control failures, theft, or fraud."},
{"code":"B-16","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"advanced","number":16,"type":"grammar","question":"If the corporation had not optimized its operational expenses, its net profit margin ________ so high today.","options":{"A":"will not be","B":"would not be","C":"had not been","D":"could not have been"},"answer_key":"B","feedback":"This is a mixed conditional. The past condition ('had not optimized') affects the current state, requiring 'would not be'."},
{"code":"B-17","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"advanced","number":17,"type":"grammar","question":"Never ________ seen such a drastic inflation adjustment on the company's financial records.","options":{"A":"the auditor had","B":"had the auditor","C":"did the auditor","D":"the auditor has"},"answer_key":"B","feedback":"Negative adverbs like 'Never' at the front of a sentence trigger subject-auxiliary inversion ('had the auditor')."},
{"code":"B-18","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"advanced","number":18,"type":"grammar","question":"Under IFRS framework, revenue ________ unless the performance obligations are fully satisfied.","options":{"A":"cannot recognize","B":"cannot be recognized","C":"should not recognize","D":"is not recognizing"},"answer_key":"B","feedback":"The modal passive structure (modal + be + Past Participle) is required since revenue cannot perform the recognition itself."},
{"code":"B-19","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"advanced","number":19,"type":"vocabulary","question":"When revenues are recognized when earned and expenses are recognized when incurred, regardless of cash movement, it is called...","options":{"A":"Cash basis accounting","B":"Accrual basis accounting","C":"Deferral accounting","D":"Inflation accounting"},"answer_key":"B","feedback":"Accrual accounting records financial transactions based on economic events rather than actual cash flows."},
{"code":"B-20","module_code":"B","module_title":"Income Statement & Performance Analysis","level":"advanced","number":20,"type":"vocabulary","question":"An analytical metric evaluating earnings relative to equity capital invested by shareholders is named...","options":{"A":"Return on Equity (ROE)","B":"Debt-to-Equity Ratio","C":"Price-Earnings Ratio","D":"Asset Turnover"},"answer_key":"A","feedback":"ROE measures profit generation efficiency per unit of owner capital invested (Net Income / Shareholders' Equity)."},
{"code":"C-01","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"basic","number":1,"type":"vocabulary","question":"Cash inflows and outflows directly related to borrowing money or issuing corporate shares are classified under...","options":{"A":"Operating Activities","B":"Investing Activities","C":"Financing Activities","D":"Adjusting Activities"},"answer_key":"C","feedback":"Financing activities deal with capital procurement from equity issues or long-term debt modifications."},
{"code":"C-02","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"basic","number":2,"type":"grammar","question":"Yesterday, the ledger accounts ________ balanced perfectly by the junior accounting team before 5 PM.","options":{"A":"are","B":"were","C":"was","D":"been"},"answer_key":"B","feedback":"The past plural constraint ('Yesterday' + 'accounts') requires the past plural passive form 'were'."},
{"code":"C-03","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"basic","number":3,"type":"vocabulary","question":"An independent examination of the financial statements of an entity to express an opinion is a/an...","options":{"A":"Taxation","B":"Auditing","C":"Bookkeeping","D":"Budgeting"},"answer_key":"B","feedback":"An audit verifies whether financial statements present a true and fair view of corporate operations."},
{"code":"C-04","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"basic","number":4,"type":"grammar","question":"The tax collector ________ the corporate financial ledger right now.","options":{"A":"examine","B":"is examining","C":"examined","D":"has examined"},"answer_key":"B","feedback":"'Right now' signals a Present Continuous tense action for a singular subject ('is examining')."},
{"code":"C-05","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"basic","number":5,"type":"vocabulary","question":"The financial statement that reports the cash receipts and cash payments of a business over a period is the...","options":{"A":"Income Statement","B":"Statement of Cash Flows","C":"Balance Sheet","D":"Retained Earnings Statement"},"answer_key":"B","feedback":"The Statement of Cash Flows categorizes cash changes into operating, investing, and financing groups."},
{"code":"C-06","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"basic","number":6,"type":"grammar","question":"The internal auditor ________ completed the preliminary financial evaluation checklist.","options":{"A":"have","B":"has","C":"having","D":"is"},"answer_key":"B","feedback":"Singular subject 'The internal auditor' requires 'has' for Present Perfect constructions."},
{"code":"C-07","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"basic","number":7,"type":"vocabulary","question":"Mandatory financial contributions imposed by the government on corporate income or transactions are called...","options":{"A":"Subsidies","B":"Taxes","C":"Dividends","D":"Fines"},"answer_key":"B","feedback":"Taxes are compulsory levies enforced by state authorities on corporate profits or activities."},
{"code":"C-08","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"intermediate","number":8,"type":"vocabulary","question":"When a firm sells services on account, the transaction increases revenue and generates a financial claim called...","options":{"A":"Accounts Payable","B":"Accounts Receivable","C":"Retained Earnings","D":"Accrued Expenses"},"answer_key":"B","feedback":"Accounts Receivable tracks open account balances due from buyers for completed deals."},
{"code":"C-09","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"intermediate","number":9,"type":"grammar","question":"The company expanded its production line ________ its net profit dropped slightly compared to the previous month.","options":{"A":"because","B":"even though","C":"therefore","D":"due to"},"answer_key":"B","feedback":"'Even though' serves as a concession conjunction to contrast expansion against profit reduction."},
{"code":"C-10","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"intermediate","number":10,"type":"vocabulary","question":"Cash flows resulting from purchasing or selling property, plant, and equipment (PPE) are classified as...","options":{"A":"Operating Activities","B":"Investing Activities","C":"Financing Activities","D":"Liquid Activities"},"answer_key":"B","feedback":"Investing activities encompass the acquisition and disposal of long-term productive assets and non-cash investments."},
{"code":"C-11","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"intermediate","number":11,"type":"grammar","question":"The company must submit its annual tax return forms ________ April 30th to avoid statutory late fees.","options":{"A":"at","B":"on","C":"before","D":"in"},"answer_key":"C","feedback":"'Before' designates a hard time constraint deadline to prevent penalties."},
{"code":"C-12","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"intermediate","number":12,"type":"vocabulary","question":"A document prepared to verify and bring the cash balance per a company's records into agreement with the bank statement balance is a...","options":{"A":"Petty Cash Log","B":"Bank Reconciliation","C":"Trial Balance","D":"General Ledger"},"answer_key":"B","feedback":"Bank reconciliation rectifies timing differences between the internal ledger cash accounts and external bank statements."},
{"code":"C-13","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"intermediate","number":13,"type":"grammar","question":"The tax regulations require that all transaction receipts ________ kept for at least five years.","options":{"A":"are","B":"be","C":"being","D":"was"},"answer_key":"B","feedback":"Mandatory commands like 'require that' invoke the subjunctive form, demanding the base verb 'be' in passive construction."},
{"code":"C-14","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"intermediate","number":14,"type":"vocabulary","question":"The risk that an auditor will mistakenly issue an unmodified audit opinion on financial statements that contain material misstatements is...","options":{"A":"Inherent Risk","B":"Control Risk","C":"Audit Risk","D":"Detection Risk"},"answer_key":"C","feedback":"Audit Risk is the combination of misstatement occurrence risks and an auditor's failure to capture them."},
{"code":"C-15","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"advanced","number":15,"type":"vocabulary","question":"During an external audit, if an auditor finds that the financial records are free from material misstatements, the company receives a/an...","options":{"A":"Adverse opinion","B":"Qualified opinion","C":"Unqualified opinion","D":"Disclaimer of opinion"},"answer_key":"C","feedback":"An Unqualified Opinion indicates clean compliance with GAAP/IFRS standards without critical errors."},
{"code":"C-16","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"advanced","number":16,"type":"grammar","question":"The chief auditor ________ the fraud scheme if he had cross-checked the bank reconciliation statements with the physical cash logs.","options":{"A":"would discover","B":"could have discovered","C":"had discovered","D":"discovered"},"answer_key":"B","feedback":"Conditional Type 3 main clause format utilizes could/would have + Past Participle to denote past capability."},
{"code":"C-17","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"advanced","number":17,"type":"vocabulary","question":"An intentional manipulation of tax accounting methods to illegally reduce tax liability is known as...","options":{"A":"Tax avoidance","B":"Tax evasion","C":"Tax deferral","D":"Tax planning"},"answer_key":"B","feedback":"Tax evasion is the illegal practice of misrepresenting profits to evade fiscal obligations. Avoidance operates within legal gaps."},
{"code":"C-18","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"advanced","number":18,"type":"grammar","question":"Rarely ________ such high discrepancies between the direct and indirect cash flow calculations.","options":{"A":"we encounter","B":"do we encounter","C":"have we encountering","D":"we do encounter"},"answer_key":"B","feedback":"Negative adverbs like 'Rarely' necessitate subject-auxiliary inversion in present statement descriptions ('do we encounter')."},
{"code":"C-19","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"advanced","number":19,"type":"vocabulary","question":"Which audit risk component represents the susceptibility of an account balance to a material misstatement assuming no related internal controls?","options":{"A":"Control Risk","B":"Inherent Risk","C":"Detection Risk","D":"Operational Risk"},"answer_key":"B","feedback":"Inherent Risk represents the natural risk level native to an environment or transaction category before controls are applied."},
{"code":"C-20","module_code":"C","module_title":"Cash Flow, Auditing & Taxation","level":"advanced","number":20,"type":"grammar","question":"The CFO requested that the corporate financial statements ________ with tax compliance disclosures before final submission.","options":{"A":"are appended","B":"be appended","C":"will be appended","D":"should append"},"answer_key":"B","feedback":"The matrix verb 'requested' triggers a mandative subjunctive structure, matching the base form passive construction 'be appended'."}
]
```

---

## 6. CATATAN AUDIT DATA (WAJIB DIBACA)

1. **Distribusi kunci jawaban timpang parah.** Dari 60 soal: **B = 46 (76,7%)**, C = 12, A = 2, **D = 0**.
   Mahasiswa yang asal pilih "B" akan lolos ~77% tanpa belajar. **Solusi teknis:** acak urutan opsi saat runtime (bukan saat seeding), lalu simpan jawaban benar berdasarkan **isi teks opsi**, bukan huruf.
2. **Urutan soal Advanced di SET B rusak** pada dokumen asli (15, 16, 20, 18, 19, 17). Sudah diurutkan ulang di file ini.
3. **Tidak ada soal yang hilang.** Verifikasi: 20 + 20 + 20 = 60 blok "Kunci Jawaban" terdeteksi.
4. Notasi matematika LaTeX pada feedback asli (`$\text{...}$`) sudah diratakan ke teks biasa agar aman disimpan di kolom database.
