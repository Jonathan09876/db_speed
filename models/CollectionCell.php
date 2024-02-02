<?php

namespace app\models;

use Yii;
use yii\bootstrap5\Html;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "collection_cell".
 *
 * @property int $collection_cell_id
 * @property int $contract_detail_id
 * @property int $term_id
 * @property float|null $monthly_charge_amount_with_tax
 * @property float|null $repayment_amount_with_tax
 * @property float|null $monthly_payment_amount_with_tax
 * @property string|null $options
 * @property string $updated
 * @property string $content_monthly_charge
 * @property string $content_repayment
 * @property string $content_monthly_payment
 */
class CollectionCell extends \yii\db\ActiveRecord
{
    static $instances = [];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'collection_cell';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contract_detail_id', 'term_id', 'updated'], 'required'],
            [['contract_detail_id', 'term_id'], 'integer'],
            [['monthly_charge_amount_with_tax', 'repayment_amount_with_tax', 'monthly_payment_amount_with_tax'], 'number'],
            [['options', 'updated', 'content_monthly_charge', 'content_repayment', 'content_monthly_payment'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'collection_cell_id' => 'Collection Cell ID',
            'contract_detail_id' => 'Contract Detail ID',
            'term_id' => 'Term ID',
            'monthly_charge_amount_with_tax' => 'Monthly Charge Amount With Tax',
            'repayment_amount_with_tax' => 'Repayment Amount With Tax',
            'monthly_payment_amount_with_tax' => 'Monthly Payment Amount With Tax',
            'options' => 'Options',
            'updated' => 'Updated',
        ];
    }

    public function getContractDetail()
    {
        return $this->hasOne(ContractDetail::class, ['contract_detail_id' => 'contract_detail_id']);
    }

    public function getTerm()
    {
        return $this->hasOne(Term::class, ['term_id' => 'term_id']);
    }

    public static function getInstance($contract_detail_id, $term_id, $static = true)
    {
        if (!isset(static::$instances[$contract_detail_id][$term_id])) {
            $instance = CollectionCell::findOne(['contract_detail_id' => $contract_detail_id, 'term_id' => $term_id]);
            if (!$instance) {
                $instance = static::createInstance($contract_detail_id, $term_id);
                $instance->updateContent();
            }
            if (!$static) {
                return $instance;
            }
            if (!isset(static::$instances[$contract_detail_id])) {
                static::$instances[$contract_detail_id] = [];
            }
            static::$instances[$contract_detail_id][$term_id] = $instance;
        }
        return static::$instances[$contract_detail_id][$term_id];
    }

    public static function createInstance($contract_detail_id, $term_id)
    {
        $instance = new CollectionCell([
            'contract_detail_id' => $contract_detail_id,
            'term_id' => $term_id,
            'updated' => date('Y-m-d H:i:s'),
        ]);
        $instance->save();
        return $instance;
    }

    public function updateContent()
    {
        $current = (new \DateTime())->setDate(date('Y'), date('n'), 1);
        $lastMonth = $current->modify('-1 month');
        $is_closed = $this->term->termDateTime < $lastMonth || \app\models\TargetTermMonthlyChargeStored::isMonthClosed($this->term->term, $this->contractDetail->leaseContract->customer->clientContract->client_corporation_id);
        $options = $this->options ? json_decode($this->options, true) : [];
        $options['is_closed'] = $is_closed;
        $monthlyCharges = $this->term->getMonthlyCharges($this->contract_detail_id);
        if ($monthlyCharges) {
            $this->monthly_charge_amount_with_tax = array_sum(array_map(function($mc){return $mc->temporaryAmountWithTax;}, $monthlyCharges));
            $options['mcid'] = implode(',', array_map(function($mc){return $mc->monthly_charge_id;}, $monthlyCharges));
            $options['mcrtid'] = implode(',', array_map(function($mc){return $mc->repaymentType->repayment_type_id;}, $monthlyCharges));
            $options['mcOrderCount'] = implode(',', array_map(function($mc){return $mc->orderCountText;}, $monthlyCharges));
            $options['mcClass'] = !$is_closed ? ' editable cell-monthly_charge-temporary_charge_amount_and_type' : '';
            $options['mcStyle'] = implode('', array_unique(array_map(function($monthlyCharge){return $monthlyCharge->repaymentType->style;}, $monthlyCharges)));
            $repayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->repayments;}, $monthlyCharges), 'array_merge', []);
            $this->repayment_amount_with_tax = array_sum(array_map(function($rp){return $rp->repayment_amount;}, $repayments));
            $options['rpid'] = implode(',', array_map(function($rp){return $rp->repayment_id;}, $repayments));
            $options['rpOrderCount'] = implode(',', array_map(function($rp){return $rp->orderCountText;}, $repayments));
            $options['rpClass'] = count($monthlyCharges) > 0 ? (count($repayments) > 0 && $is_closed ? 'editable cell-repayment-repayment_amount' : 'registerable cell-repayment-repayment_amount') : '';
            $options['rpStyle'] = implode('', array_unique(array_map(function($rp){return $rp->repaymentType->style ? $rp->repaymentType->style : '';}, $repayments)));
            $advanceRepayments = array_reduce(array_map(function($monthlyCharge){return $monthlyCharge->getAdvanceRepayments()->all();}, $monthlyCharges), 'array_merge', []);
            if ($advanceRepayments) {
                $this->repayment_amount_with_tax += array_sum(array_map(function($rp){return $rp->repayment_amount;}, $advanceRepayments));
                $options['rpClass'] = '';
                $options['rpStyle'] = RepaymentType::findOne(5)->style;
            }
        }
        else {
            $this->monthly_charge_amount_with_tax = 0;
            $this->repayment_amount_with_tax = 0;
            unset($options['mcid']);
            unset($options['mcrtid']);
            unset($options['mcOrderCount']);
            unset($options['mcClass']);
            unset($options['mcStyle']);
            unset($options['rpid']);
            unset($options['rpOrderCount']);
            unset($options['rpClass']);
            unset($options['rpStyle']);
        }
        $monthlyPayments = $this->term->getMonthlyPayments($this->contract_detail_id);
        if ($monthlyPayments) {
            $this->monthly_payment_amount_with_tax = array_sum(array_map(function($mp){return $mp->amountWithTax;}, $monthlyPayments));
            $options['mpOrderCount'] = implode(',', array_map(function($mp){return $mp->orderCount;}, $monthlyPayments));
            $options['mpid'] = implode(',', array_map(function($mp){return $mp->monthly_payment_id;}, $monthlyPayments));
        }
        else {
            $this->monthly_payment_amount_with_tax = 0;
            unset($options['mpOrderCount']);
            unset($options['mpid']);
        }
        $this->options = json_encode($options);
        $this->updated = date('Y-m-d H:i:s');
        $content_monthly_charge = $this->render($this->term, 'both', 'monthly_charge', true);
        $this->content_monthly_charge = json_encode([
            'chargeOnly' => $content_monthly_charge,
            'paymentOnly' => $content_monthly_charge,
            'both' => $content_monthly_charge
        ]);
        $content_repayment = $this->render($this->term, 'both', 'repayment', true);
        $this->content_repayment = json_encode([
            'chargeOnly' => $this->render($this->term, 'chargeOnly', 'repayment', true),
            'paymentOnly' => $content_repayment,
            'both' => $content_repayment
        ]);
        $content_monthly_payment = $this->render($this->term, 'both', 'monthly_payment', true);
        $this->content_monthly_payment = json_encode([
            'chargeOnly' => $content_monthly_payment,
            'paymentOnly' => $content_monthly_payment,
            'both' => $content_monthly_payment
        ]);
        $this->updated = (new \DateTime())->format('Y-m-d H:i:s');
        $this->save();
    }

    public function render($term, $mode, $target, $force = null)
    {
        if ($force) {
            $options = json_decode($this->options, true);
            $currentTerm = new \DateTime(date('Y-m-01'));
            switch($target) {
                case 'monthly_charge':
                    if (isset($options['mcid'])) {
                        $diff = $this->monthly_charge_amount_with_tax - ($this->repayment_amount_with_tax ?? 0);
                        $bgColorClass = $diff > 0 ? ($term->termDateTime >= $currentTerm ? '' : ' deficient') : ($this->monthly_charge_amount_with_tax != 0 ? ' paid' : '');
                        if ($term->termDateTime == $currentTerm && $diff > 0 && $options['rpid']) {
                            $bgColorClass = ' deficient';
                        }
                        if ($diff < 0) {
                            $bgColorClass = ' paid';
                        }
                    }
                    else {
                        $bgColorClass = '';
                    }
                    $rtids = isset($options['mcrtid']) ? explode(',', $options['mcrtid']) : [];
                    $render_order = $this->monthly_charge_amount_with_tax > 0 || count(array_intersect($rtids, [11,12])) == 0;
                    return Html::tag('td', $render_order ? ($options['mcOrderCount'] ?? '') : '', ['class' => 'text-end' . $bgColorClass, 'data-mcrtid' => $options['mcrtid'] ?? '']) .
                        Html::tag('td', $render_order && isset($options['mcid']) ? number_format($this->monthly_charge_amount_with_tax,0) : '', ['class' => 'text-end' . $bgColorClass . ($options['mcClass'] ?? ''), 'data-id' => $options['mcid'] ?? '', 'style' => ($options['mcStyle'] ?? '')]);
                case 'repayment':
                    if (isset($options['mcid'])) {
                        $diff = $this->monthly_charge_amount_with_tax - ($this->repayment_amount_with_tax ?? 0);
                        $bgColorClass = $diff > 0 ? ($term->termDateTime >= $currentTerm ? '' : ' deficient') : ($this->monthly_charge_amount_with_tax != 0 ? ' paid' : '');
                        if ($term->termDateTime == $currentTerm && $diff > 0 && $options['rpid']) {
                            $bgColorClass = ' deficient';
                        }
                        $deficientClass = $options['rpid'] ? ($diff > 0 && $options['is_closed'] ? ' editable cell-repayment-repayment_amount' : '') : ($options['mcid'] && $options['is_closed'] ? ' registerable cell-repayment-repayment_amount' : '');
                        if ($diff < 0) {
                            $bgColorClass = ' paid';
                        }
                    }
                    else {
                        $bgColorClass = '';
                        $deficientClass = '';
                    }
                    if ($mode == 'chargeOnly') {
                        $class = 'border-bottom text-end';
                    }
                    else {
                        $class = 'text-end';
                    }
                    $rtids = isset($options['mcrtid']) ? explode(',', $options['mcrtid']) : [];
                    $render_order = ($this->repayment_amount_with_tax > 0 || $this->monthly_charge_amount_with_tax > 0) || count(array_intersect($rtids, [11,12])) == 0;
                    return Html::tag('td', $render_order ? ($options['rpOrderCount'] ?? '') : '', ['class' => $class . $bgColorClass]) .
                        Html::tag('td', $render_order && isset($options['rpid']) ? number_format($this->repayment_amount_with_tax,0) : '', ['class' => $class . $bgColorClass . $deficientClass, 'style' => $options['rpStyle'] ?? '', 'data-id' => $options['rpid'] ?? $options['mpid'] ?? '']);
                case 'monthly_payment':
                    $mp_zero = $this->contractDetail->monthly_payment == 0 && $this->contractDetail->monthly_payment_unfixed == 0;
                    $mpBgClass = $mp_zero && $this->contractDetail->leaseServicer->for_internal ? ' bg-gray' : '';
                    $rtids = isset($options['mcrtid']) ? explode(',', $options['mcrtid']) : [];
                    $render_order = $this->contractDetail->monthly_payment_unfixed != 0 || $this->monthly_payment_amount_with_tax > 0 || (count($rtids) != 0 && count(array_intersect($rtids, [11,12])) == 0);
                    $render_order = $render_order && !(isset($this->contractDetail->leaseContract->currentStatus) && $this->contractDetail->leaseContract->currentStatus->lease_contract_status_type_id == 10 && $this->monthly_payment_amount_with_tax == 0);
                    return Html::tag('td', !$mp_zero && $render_order ? (isset($options['mpid']) ? $options['mpOrderCount'] : '') : '', ['class' => 'text-end payment-cell border-bottom' . $mpBgClass]) .
                        Html::tag('td', !$mp_zero &&$render_order && isset($options['mpid']) ? number_format($this->monthly_payment_amount_with_tax,0) : '', ['class' => 'text-end payment-cell border-bottom' . $mpBgClass]);
            }
        }
        else {
            $attr = "content_{$target}";
            if (!empty($this->$attr)) {
                $content = json_decode($this->$attr, true);
                return $content[$mode];
            }
            else {
                return $this->render($term, $mode, $target, true);
            }
        }
    }

    public function renderExports(&$row, $target)
    {
        $options = json_decode($this->options, true);
        switch($target) {
            case 'monthly_charge':
                $row[] = $options['mcOrderCount'] ?? '';
                $row[] = isset($options['mcid']) ? $this->monthly_charge_amount_with_tax : '';
                break;
            case 'repayment':
                $row[] = $options['rpOrderCount'] ?? '';
                $row[] = isset($options['rpid']) ? $this->repayment_amount_with_tax : '';
                break;
            case 'monthly_payment':
                $row[] = isset($options['mpid']) ? $options['mpOrderCount'] : '';
                $row[] = isset($options['mpid']) ? $this->monthly_payment_amount_with_tax : '';
                break;
        }
    }

    public static function renderRepaymentCell($values, &$index, $pos)
    {
        $options = json_decode($values['options'], true);
        if (isset($options['mcid'])) {
            $mcids = explode(',', $options['mcid']);
            $monthlyCharges = array_map(function($mcid){return MonthlyCharge::findOne($mcid);}, $mcids);
            $repayments = array_reduce(array_map(function($mc){return $mc->repayments;}, $monthlyCharges), 'array_merge', []);
        }
        else {
            $monthlyCharges = [];
            $repayments = [];
        }
        switch($pos) {
            case 1:
                return '<td>'.($options['mcOrderCount'] ?? '').'</td><td class="text-end">'.number_format($values['monthly_charge_amount_with_tax']).'</td>';
            case 2:
                if ($repayments) {
                    return '<td>'.($options['rpOrderCount'] ?? '').'</td><td class="text-end">'.number_format($values['repayment_amount_with_tax']).'</td>';
                }
                else if ($monthlyCharges) {
                    $tag = '<td></td>';
                    $i = 0;
                    foreach($monthlyCharges as $monthlyCharge) {
                        $amount = $monthlyCharge->temporaryAmountWithTax;
                        $tag .= ($i == 0 ? '<td>' : '');
                        $tag .= Html::input('hidden', "Repayment[{$index}][monthly_charge_id]", $monthlyCharge->monthly_charge_id);
                        $tag .= Html::input('text', "Repayment[{$index}][repayment_amount]", $amount, ['class' => 'form-control formatted', 'data' => ['amount' => $amount, 'priority' => $values['priority'], 'term' => $values['term']]]);
                        $tag .= ($i < count($monthlyCharges) - 1 ? '' : '</td>');
                        $i++;
                        $index++;
                    }
                    return $tag;
                }
                break;
            case 3:
                if ($repayments) {
                    $tag = '';
                    $i = 0;
                    foreach($repayments as $repayment) {
                        $tag .= ($i == 0 ? '<td>' : '');
                        $tag .= $repayment->orderCount;
                        $tag .= ($i < count($repayments) - 1 ? '<br />' : '</td>');
                        $i++;
                    }
                    $i = 0;
                    foreach($repayments as $repayment) {
                        $amount = ($repayment->monthlyCharge->amountWithTax - $repayment->repayment_amount);
                        $tag .= ($i == 0 ? '<td>' : '');
                        $tag .= Html::input('hidden', "Repayment[{$index}][repayment_id]", $repayment->repayment_id);
                        $tag .= Html::input('text', "Repayment[{$index}][additional_repayment_amount]", $amount, ['class' => 'form-control formatted', 'data' => ['amount' => $amount, 'priority' => $values['priority'], 'term' => $values['term']]]);
                        $tag .= ($i < count($repayments) - 1 ? '' : '</td>');
                        $i++;
                        $index++;
                    }
                    return $tag;
                }
                else if ($monthlyCharges) {
                    return '<td></td><td></td>';
                }
                break;
            case 4:
                $monthlyCharge = $monthlyCharges[0];
                return Html::tag('td', $monthlyCharge->memo, ['colspan' => 2, 'class' => 'border-bottom-strong editable cell-monthly_charge-memo', 'data-id' => $monthlyCharge->monthly_charge_id]);
                break;
        }
    }
}
