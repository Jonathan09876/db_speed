<?php

namespace app\components;

use yii\base\Component;

class DelinquencyAlternativeRowExporter extends Component
{
    public $model;
    public $terms;
    public $searchModel;
    public $dataProvider;

    public function export()
    {
        $query = clone($this->dataProvider->query);
        $query
            ->select([
                '(SELECT CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE t.term >= application_from AND t.term <= IFNULL(application_to, "2099-12-31")) END FROM tax_application ta WHERE ta.tax_application_id=cd.tax_application_id) as `tax_rate`',
                'SUM(`clc`.`monthly_charge_amount_with_tax` - `clc`.`repayment_amount_with_tax`) as `delinquencies`',
            ])
            ->groupBy(['tax_rate'])
            ->andWhere(['lc.customer_id' => $this->model->leaseContract->customer_id]);
        $rates = $query->createCommand()->queryColumn();
        $rowspan = count($rates) + 2;
        $detailQuery = clone($this->dataProvider->query);
        $detailQuery
            ->select([
                'cd.contract_detail_id',
                'SUM(`clc`.`monthly_charge_amount_with_tax` - `clc`.`repayment_amount_with_tax`) as `delinquencies`',
            ])
            ->groupBy(['cd.contract_detail_id'])
            ->andWhere(['lc.customer_id' => $this->model->leaseContract->customer_id]);
        $sql = $detailQuery->createCommand()->rawSql;
        $contract_detail_ids = $detailQuery->createCommand()->queryColumn();
        $detail = $this->model;
        $customer = $this->model->leaseContract->customer;
        $repaymentPattern = $customer->clientContract->repaymentPattern;
        $repaymentAmounts = [];
        $wholeRepaymentAmounts = [];
        $bgColorClasses = [];
        $delinquencies = 0;
        $targetTerm = preg_replace('/(\d+)年(\d+)月/', '$1-$2-01', $this->searchModel->target_term);
        $currentMonthTerm = \app\models\Term::findOne(['term' => $targetTerm]);

        $row1 = [
            $customer->customer_code,
            $customer->clientContract->repaymentPattern->name,
            $customer->getName(),
            $customer->salesPerson->name,
        ];

        $prev = $this->terms[0]->termDateTime->modify('-1 month');
        $prevTerm = \app\models\Term::findOne(['term' => $prev->format('Y-m-d')]);
        $remains = 0;
        $details = \app\models\ContractDetail::find()->where(['contract_detail_id' => $contract_detail_ids])->all();
        foreach($details as $dtl) {
            $remains += $dtl->getChargeRemains($prevTerm);
        }
        $row1 = array_merge($row1, [
            $remains,
            '回収予定'
        ]);
        $chargesTotal = 0;
        foreach($this->terms as $term) {
            $cellsQuery = \app\models\CollectionCell::find()->where([
                'contract_detail_id' => $contract_detail_ids,
                'term_id' => $term->term_id
            ])
                ->select(['SUM(IFNULL(`monthly_charge_amount_with_tax`, 0)) as `charges`', 'SUM(IFNULL(`repayment_amount_with_tax`, 0)) as `repayments`']);
            $cells = $cellsQuery->createCommand()->queryOne();
            $chargesTotal += $cells['charges'];
            $row1[] = $cells['charges'];
        }
        foreach($contract_detail_ids as $detail_id) {
            $delinquencies += \app\models\MonthlyCharge::getRelativeShortage($detail_id, $this->searchModel->target_term);
        }
        $row1[] = $chargesTotal;
        $row1[] = $delinquencies;

        $rowIndex = 0;
        $rows = [];
        foreach($rates as $rate) {
            $rows[$rowIndex] = [
                $customer->customer_code,
                $customer->clientContract->repaymentPattern->name,
                $customer->getName(),
                $customer->salesPerson->name,
                '',
                sprintf('%d%%回収額', $rate * 100),
            ];
            $collectionTotal = 0;
            foreach($this->terms as $term) {
                $cellsQuery = \app\models\CollectionCell::find()->alias('clc')
                    ->innerJoin('contract_detail cd', 'clc.contract_detail_id=cd.contract_detail_id')
                    ->innerJoin('term t', 'clc.term_id=t.term_id')
                    ->where([
                        '(SELECT CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE t.term >= application_from AND t.term <= IFNULL(application_to, "2099-12-31")) END FROM tax_application ta WHERE ta.tax_application_id=cd.tax_application_id)' => $rate,
                        'clc.contract_detail_id' => $contract_detail_ids,
                        'clc.term_id' => $term->term_id
                    ]);
                $repaymentAmounts[$term->term] = 0;
                foreach($cellsQuery->each() as $collectionCell) {
                    $repayments = $collectionCell->term->getCurrentRepayments($collectionCell->contract_detail_id);
                    $repayment_total = array_sum(array_map(function($rp){return $rp->repayment_amount;}, $repayments));
                    $repaymentAmounts[$term->term] += $repayment_total;
                    if (!isset($wholeRepaymentAmounts[$term->term])) {
                        $wholeRepaymentAmounts[$term->term] = 0;
                    }
                    $wholeRepaymentAmounts[$term->term] += $repayment_total;
                    $collectionTotal += $repayment_total;
                }
                if ($repaymentAmounts[$term->term] > 0) {
                    $rows[$rowIndex][] = $repaymentAmounts[$term->term];
                }
                else {
                    $rows[$rowIndex][] = '';
                }
            }
            $rows[$rowIndex][] = $collectionTotal;
            $rowIndex++;
        }

        $row3 = [
            $customer->customer_code,
            $customer->clientContract->repaymentPattern->name,
            $customer->getName(),
            $customer->salesPerson->name,
            '',
            '残額',
        ];
        foreach($this->terms as $term) {
            if (isset($wholeRepaymentAmounts[$term->term])) {
                $remains -= $wholeRepaymentAmounts[$term->term];
            }
            $firstTerm = new \DateTime(min($this->model->monthlyChargeSpan->first_term, $this->model->monthlyPaymentSpan->first_term));
            $row3[] = $term->termDateTime >= $firstTerm ? $remains : '';
        }
        $row3[] = $remains;
        return array_merge([$row1], $rows, [$row3]);
    }
}