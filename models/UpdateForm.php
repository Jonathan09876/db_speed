<?php

namespace app\models;

use Yii;
use yii\db\Exception;
use yii\helpers\VarDumper;
use yii\web\UploadedFile;

class UpdateForm extends \yii\base\Model
{
    use Importer;

    public $file_repayment;
    public $file_payment;

    public function rules()
    {
        return [
            [['file_repayment', 'file_payment'], 'file']
        ];
    }

    public function attributeLabels()
    {
        return [
            'file_repayment' => '回収情報CSV',
            'file_payment' => '支払情報CSV'
        ];
    }

    public function beforeValidate()
    {
        $this->file_repayment = UploadedFile::getInstance($this, 'file_repayment');
        $this->file_payment = UploadedFile::getInstance($this, 'file_payment');
        return true;
    }

    public function loadRepaymentFile()
    {
        if ($this->file_repayment) {
            $path = $this->file_repayment->tempName;
            $rows = $this->readCsv($path);
            $model = new ImportRepaymentUpdate();
            $count = 0;
            while($row = array_shift($rows)) {
                if ($count++ == 0) continue;
                if (count($model->attributes()) != count($row)) {
                    throw new Exception('データ列数が仕様と異なります。'.VarDumper::dumpAsString($row));
                }
                $attributes = array_combine($model->attributes(), $row);
                $instance = new ImportRepaymentUpdate($attributes);
                if (empty($instance->registered)) {
                    $instance->registered = $instance->processed;
                }
                if (!$instance->save()) {
                    throw new Exception(print_r([$instance->attributes,$instance->firstErrors],1));
                }
            }
        }
    }


    public function loadPaymentFile()
    {
        if ($this->file_payment) {
            $path = $this->file_payment->tempName;
            $rows = $this->readCsv($path);
            $model = new ImportPaymentUpdate();
            $count = 0;
            while($row = array_shift($rows)) {
                if ($count++ == 0) continue;
                if (count($model->attributes()) != count($row)) {
                    throw new Exception('データ列数が仕様と異なります。'.VarDumper::dumpAsString($row));
                }
                $attributes = array_combine($model->attributes(), $row);
                $instance = new ImportPaymentUpdate($attributes);
                if (!$instance->save()) {
                    throw new Exception(print_r($instance->firstErrors,1));
                }
            }
        }
    }

    public function loadFiles()
    {
        Yii::$app->db->createCommand('TRUNCATE `import_repayment_update`;')->execute();
        Yii::$app->db->createCommand('TRUNCATE `import_payment_update`;')->execute();
        $this->loadRepaymentFile();
        $this->loadPaymentFile();
        $registered_rows = [
            'repayments' => ImportRepaymentUpdate::find()->count(),
            'payments' => ImportPaymentUpdate::find()->count(),
        ];
        return $registered_rows;
    }

    public function processImporting()
    {
        $transaction = Yii::$app->db->beginTransaction();
        $registered = [
            'customer' => 0,
            'lease_contract' => 0,
            'contract_detail' => 0,
            'repayment' => 0,
            'payment' => 0,
        ];
        try {
            //顧客情報取り込み
            $query = ImportCustomer::find();
            foreach($query->each() as $row) {
                $contract = new ClientContract([
                    'client_corporation_id' => $row->client_corporation_id,
                    'repayment_pattern_id' => $row->repayment_pattern_id,
                    'account_transfer_code' => empty($row->account_transfer_code) ? '-未登録-' : $row->account_transfer_code,
                ]);
                if (!$contract->save()) {
                    throw new Exception(VarDumper::dumpAsString($contract->firstErrors,10, 0));
                }
                $bankAcount = new BankAccount([
                    'bank_name' => empty($row->bank_name) ? '-未登録-' : $row->bank_name,
                    'bank_code' => empty($row->bank_code) ? '0000' : str_pad($row->bank_code, 4, '0', STR_PAD_LEFT),
                    'branch_name' => empty($row->branch_name) ? '-未登録-' : $row->branch_name,
                    'branch_code' => empty($row->branch_code) ? '000' : str_pad($row->branch_code, 3, '0', STR_PAD_LEFT),
                    'account_type' => empty($row->account_type) ? 1 : $row->account_type,
                    'account_number' => empty($row->account_number) ? '0000000' : str_pad($row->account_number, 7, '0', STR_PAD_LEFT),
                    'account_name' => empty($row->account_name) ? '-未登録-' : $row->account_name,
                    'account_name_kana' => empty($row->account_name_kana) ? '-未登録-' : $row->account_name_kana,
                ]);
                if (!$bankAcount->save()) {
                    throw new Exception(VarDumper::dumpAsString($bankAcount->firstErrors,10,0));
                }
                echo ".";
                $location = new Location([
                    'zip_code' => empty($row->zip_code) ? '000-0000' : $row->zip_code,
                    'address' => empty($row->address) ? '-未登録-' : $row->address,
                    'address_optional' => empty($row->address_optional) ? '-未登録-' : $row->address_optional,
                ]);
                if (!$location->save()) {
                    throw new Exception(VarDumper::dumpAsString($location->firstErrors,10,0));
                }
                echo ".";
                $customer = new Customer([
                    'customer_code' => str_pad($row->customer_code, 4, '0', STR_PAD_LEFT),
                    'client_contract_id' => $contract->client_contract_id,
                    'name' => $row->name,
                    'position' => $row->position,
                    'transfer_name' => $row->transfer_name,
                    'use_transfer_name' => !!$row->use_transfer_name ? 1 : 0,
                    'bank_account_id' => $bankAcount->bank_account_id,
                    'sales_person_id' => 9,
                    'location_id' => $location->location_id,
                    'memo' => $row->memo,
                ]);
                echo ".";
                if (!$customer->save()) {
                    throw new Exception(VarDumper::dumpAsString($customer->firstErrors,10,0));
                }
                else {
                    $registered['customer'] += 1;
                }
                empty($row->phone_1) or Phone::register($customer->customer_id, $row->phone_1);
                empty($row->phone_2) or Phone::register($customer->customer_id, $row->phone_2);
                empty($row->phone_3) or Phone::register($customer->customer_id, $row->phone_3);
                empty($row->mail_address_1) or MailAddress::register($customer->customer_id, $row->mail_address_1);
                empty($row->mail_address_2) or MailAddress::register($customer->customer_id, $row->mail_address_2);
                empty($row->mail_address_3) or MailAddress::register($customer->customer_id, $row->mail_address_3);
                echo ".";

                //契約情報取り込み
                $query = ImportLeaseContract::find()->where(['import_customer_id' => $row->import_customer_id]);
                foreach($query->each() as $contractRow) {
                    $target = new LeaseTarget([
                        'name' => empty($contractRow->target_name) ? '-' : $contractRow->target_name,
                        'registration_number' => $contractRow->registration_number,
                        'attributes' => $contractRow->target_attributes,
                        'memo' => $contractRow->target_memo,
                    ]);
                    if (!$target->save()) {
                        throw new Exception(VarDumper::dumpAsString($target->firstErrors,10,0));
                    }
                    echo "+";
                    $contractPattern = ContractPattern::findOne([
                        'client_corporation_id' => $contract->client_corporation_id,
                        'code' => $contractRow->contract_pattern,
                        'removed' => null
                    ]);
                    $leaseContract = new LeaseContract([
                        'customer_id' => $customer->customer_id,
                        'lease_target_id' => $target->lease_target_id,
                        'contract_pattern_id' => $contractPattern->contract_pattern_id,
                        'contract_number' => str_pad($contractRow->contract_number, 4, '0', STR_PAD_LEFT),
                        'contract_code' => str_pad($contractRow->contract_code, 4, '0', STR_PAD_LEFT),
                        'contract_sub_code' => $contractRow->contract_sub_code,
                        'contract_date' => $contractRow->contract_date,
                        'registration_incomplete' => 0,
                        'collection_application_complete' => 1,
                        'disp_order' => LeaseContract::find()->max('lease_contract_id')+1.
                    ]);
                    if (!$leaseContract->save()) {
                        throw new Exception(VarDumper::dumpAsString($leaseContract->firstErrors,10,0));
                    }
                    else {
                        $registered['lease_contract'] += 1;
                    }
                    echo "+";

                    //詳細情報取り込み
                    $query = ImportContractDetail::find()->where(['import_lease_contract_id' => $contractRow->import_lease_contract_id]);
                    foreach($query->each() as $detailRow) {
                        $servicer = LeaseServicer::findOne([
                            'shorten_name' => $detailRow->lease_servicer,
                            'removed' => null
                        ]);
                        if (!$servicer) {
                            throw new Exception('リース会社が特定出来ません。マスタの登録内容とレコードをご確認ください。');
                        }
                        $contractTypes = preg_match('/メンテ/', $detailRow->contract_type) ? 'meintenance' : 'ordinary';
                        $taxApprication = TaxApplication::findOne([
                            'application_name' => $detailRow->tax_application,
                            'removed' => null
                        ]);
                        if (!$taxApprication) {
                            throw new Exception('税区分指定が特定出来ません。マスタの登録内容とレコードをご確認ください。');
                        }
                        $patterns = [
                            '0' => 'floor',
                            '1' => 'ceil',
                            '2' => 'roundup',
                        ];
                        $contractDetail = new ContractDetail([
                            'lease_contract_id' => $leaseContract->lease_contract_id,
                            'lease_servicer_id' => $servicer->lease_servicer_id,
                            'contract_type' => $contractTypes,
                            'tax_application_id' => $taxApprication->tax_application_id,
                            'fraction_processing_pattern' => $patterns[$detailRow->fraction_processing_pattern],
                            'term_start_at' => $detailRow->term_start_at,
                            'term_end_at' => $detailRow->term_end_at,
                            'term_months_count' => $detailRow->term_months_count,
                            'lease_start_at' => (new \DateTime($detailRow->lease_start_at ? $detailRow->lease_start_at : $detailRow->term_start_at))->format('Y年n月'),
                            'collection_start_at' => (new \DateTime($detailRow->collection_start_at))->format('Y年n月'),
                            'payment_start_at' => (new \DateTime($detailRow->payment_start_at))->format('Y年n月'),
                            'first_collection_count' => $detailRow->collection_latency ?? 1,
                            'first_payment_count' => $detailRow->payment_latency ?? 1,
                            'monthly_charge' => $detailRow->monthly_charge,
                            'monthly_payment' => empty($detailRow->monthly_payment) ? 0 : $detailRow->monthly_payment,
                            'bonus_month_1' => $detailRow->bonus_month_1,
                            'bonus_additional_charge_1' => $detailRow->bonus_additional_charge_1,
                            'bonus_additional_payment_1' => $detailRow->bonus_additional_payment_1,
                            'bonus_month_2' => $detailRow->bonus_month_2,
                            'bonus_additional_charge_2' => $detailRow->bonus_additional_charge_2,
                            'bonus_additional_payment_2' => $detailRow->bonus_additional_payment_2,
                            'total_charge_amount' => $detailRow->total_charge_amount,
                            'total_payment_amount' => $detailRow->total_payment_amount,
                            'advance_repayment_count' => $detailRow->advance_repayment_count,
                            'advance_payment_count' => $detailRow->advance_payment_count,
                            'collection_latency' => $detailRow->collection_latency,
                            'payment_latency' => $detailRow->payment_latency
                        ]);
                        if (!$contractDetail->save()) {
                            throw new Exception(VarDumper::dumpAsString($contractDetail->firstErrors,10,0));
                        }
                        else {
                            $registered['contract_detail'] += 1;
                        }
                        echo "-";

                        //回収情報取り込み
                        $query = ImportRepayment::find()->where([
                            'import_contract_detail_id' => $detailRow->import_contract_detail_id
                        ]);
                        echo "\nR[{$detailRow->import_contract_detail_id}]";
                        foreach($query->each() as $repaymentRow) {
                            $repaymentType = RepaymentType::findOne(['type' => $repaymentRow->repayment_type, 'removed' => null]);
                            if (!$repaymentType) {
                                $repaymentType = new RepaymentType([
                                    'type' => $repaymentRow->repayment_type,
                                ]);
                                $repaymentType->save();
                                //throw new Exception('回収方法が特定出来ません。登録情報をご確認ください。');
                            }
                            $repaymentPocessed = new \DateTime((new \DateTime($repaymentRow->processed))->format('Y-m-01'));
                            $repaymentRegistered = new \DateTime((new \DateTime($repaymentRow->registered))->format('Y-m-01'));
                            $term = ($repaymentPocessed->format('Ym') < $repaymentRegistered->format('Ym')) ? $repaymentRegistered : $repaymentPocessed;
                            if ($contract->repaymentPattern->target_month == 'next') {
                                $term->modify('-1 month');
                            }
                            /**
                             * 事前入金対応
                             * 期間前入金に対応させる。契約条件により設定された回収計画に当てはまらない「事前の」入金のみ対応
                             * 登録日が回収初月より以前でなかったら弾く
                             * 複数回の事前入金は初回回収分から割り当てる
                             */
                            if ($repaymentType->repayment_type_id == 14) {
                                //事前振込
                                $monthlyCharges = MonthlyCharge::find()->alias('mc')
                                    ->leftJoin('repayment r', 'mc.monthly_charge_id=r.monthly_charge_id')
                                    ->where([
                                        'mc.contract_detail_id' => $contractDetail->contract_detail_id,
                                    ])->orderBy(['monthly_charge_id' => SORT_ASC])->all();
                                $others_registered = false;
                                $is_post = false;
                                $monthlyCharge = array_shift($monthlyCharges);
                                do {
                                    if (!$monthlyCharge) {
                                        break;
                                    }
                                    /*
                                    if ((new \DateTime($monthlyCharge->term))->format('Ym') < (new \DateTime($repaymentRow->registered))->format('Ym')) {
                                        $is_post = true;
                                        break;
                                    }
                                    */
                                    $repayment = $monthlyCharge->repayments[0] ?? false;
                                    if (!$repayment) {
                                        break;
                                    }
                                    else if ($repayment->repayment_type_id != 14) {
                                        $others_registered = true;
                                        break;
                                    }
                                } while($monthlyCharge = array_shift($monthlyCharges));
                                if (!$monthlyCharge || $others_registered || $is_post) {
                                    echo "=";
                                    continue;
                                }
                                $monthlyCharge->term = $term->format('Y-m-01');
                                $monthlyCharge->transfer_date = $term->format("Y-m-{$contract->repaymentPattern->transfer_date}");
                                $monthlyCharge->save();
                            }
                            else {
                                $monthlyCharge = MonthlyCharge::find()->alias('mc')
                                    ->leftJoin('repayment r', 'mc.monthly_charge_id=r.monthly_charge_id')
                                    ->where(['and',
                                        ['mc.contract_detail_id' => $contractDetail->contract_detail_id],
                                        ['DATE_FORMAT(mc.term, "%Y%m")' => $term->format('Ym')],
                                        ['r.repayment_id' => null],
                                    ])->limit(1)->one();
                                /*
                                                                if (!$monthlyCharge) {
                                                                    //throw new Exception('関連する回収予定情報が登録されていません。'.json_encode($repaymentRow->attributes));
                                                                    echo "=";
                                                                    continue;
                                                                }
                                */
                                if (!$monthlyCharge) {
                                    $monthlyCharge = MonthlyCharge::find()->alias('mc')
                                        ->leftJoin('repayment r', 'mc.monthly_charge_id=r.monthly_charge_id')
                                        ->where(['and',
                                            ['mc.contract_detail_id' => $contractDetail->contract_detail_id],
                                            ['>', 'mc.term', $term->format('Y-m-01')],
                                            ['r.repayment_id' => null],
                                        ])->orderBy(['monthly_charge_id' => SORT_ASC])->limit(1)->one();
                                    if ($monthlyCharge) {
                                        do {
                                            $monthlyCharge->slideTerm(-1);
                                            $monthlyCharge->refresh();
                                        } while((new \DateTime($monthlyCharge->term))->format('Ym') > $term->format('Ym'));
                                    }
                                    else {
                                        echo "=";
                                        continue;
                                    }
                                }
                            }
                            $repayment = new Repayment([
                                'scenario' => 'import',
                                'contract_detail_id' => $contractDetail->contract_detail_id,
                                'monthly_charge_id' => $monthlyCharge->monthly_charge_id,
                                'repayment_type_id' => $repaymentType->repayment_type_id,
                                'repayment_amount' => $repaymentRow->repayment_amount,
                                'processed' => $repaymentRow->registered,//$repaymentRow->processed,
                                'registered' => $repaymentRow->registered,
                            ]);
                            if (!$repayment->save()) {
                                echo "[SKIP:{$repayment->repaymentType->type}]";
                                //throw new Exception('回収情報が登録出来ません。' . VarDumper::dumpAsString($repayment->errors));
                            }
                            else {
                                $registered['repayment'] += 1;
                            }
                            echo "*";
                        }
                        echo "\n";
                        //支払情報取り込み
                        $query = ImportPayment::find()->where([
                            'import_contract_detail_id' => $detailRow->import_contract_detail_id
                        ]);
                        echo "P[{$detailRow->import_contract_detail_id}]";
                        foreach($query->each() as $paymentRow) {
                            $monthlyPayment = MonthlyPayment::find()->alias('mp')
                                ->leftJoin('lease_payment lp', 'mp.monthly_payment_id=lp.monthly_payment_id')
                                ->where([
                                    'mp.contract_detail_id' => $contractDetail->contract_detail_id,
                                    'DATE_FORMAT(mp.term, "%Y%m")'=>(new \DateTime($paymentRow->processed))->format('Ym'),
                                    'lp.lease_payment_id' => null,
                                ])->limit(1)->one();
                            if (!$monthlyPayment) {
                                echo "=";
                                continue;
                                //throw new Exception('関連する支払予定情報が登録されていません。'.json_encode($paymentRow->attributes));
                            }
                            $payment = new LeasePayment([
                                'contract_detail_id' => $contractDetail->contract_detail_id,
                                'monthly_payment_id' => $monthlyPayment->monthly_payment_id,
                                'payment_amount' => $paymentRow->payment_amount,
                                'processed' => $paymentRow->processed,
                                'registered' => $paymentRow->registered
                            ]);
                            if (!$payment->save()) {
                                throw new Exception('支払情報が登録出来ません。' . VarDumper::dumpAsString($payment->firstErrors,10,0));
                            }
                            else {
                                $registered['payment'] += 1;
                            }
                            echo "~";
                        }
                        echo "\n";
                    }
                    if($contractRow->contract_status) {
                        $statusType = LeaseContractStatusType::find()->where(['type' => $contractRow->contract_status])->limit(1)->one();
                        if ($statusType) {
                            LeaseContractStatus::register($leaseContract->lease_contract_id, $statusType->lease_contract_status_type_id);
                        }
                    }
                }
                echo "\n{$row->name} 登録しました。\n";
            }
            /*
            Yii::$app->db->createCommand('TRUNCATE `import_customer`;')->execute();
            Yii::$app->db->createCommand('TRUNCATE `import_lease_contract`;')->execute();
            Yii::$app->db->createCommand('TRUNCATE `import_contract_detail`;')->execute();
            Yii::$app->db->createCommand('TRUNCATE `import_repayment`;')->execute();
            Yii::$app->db->createCommand('TRUNCATE `import_payment`;')->execute();
            */
            $transaction->commit();
            return $registered;
        } catch(\Throwable $e) {
            $transaction->rollback();
            echo $e->getMessage();
            VarDumper::dump([$e->getTraceAsString(), $e]);
        }
        return [
            'customer' => 0,
            'lease_contract' => 0,
            'contract_detail' => 0,
            'repayment' => 0,
            'payment' => 0,
        ];
    }

    public function processUpdating()
    {
        $transaction = Yii::$app->db->beginTransaction();
        $registered = [
            'repayment' => 0,
            'payment' => 0,
        ];
        try {
            //回収情報取り込み
            //import_contract_numberから対象となるcontractDetailを定義
            $contractNumbers = ImportRepaymentUpdate::find()
                ->select(['import_contract_number'])
                ->distinct(true)
                ->column();
            foreach($contractNumbers as $contractNumber) {
                $query = LeaseContract::find()->where(['code_search' => $contractNumber]);
                if ($query->count() != 1) {
                    throw new \Exception('対象契約が見つからないか、複数存在しています。');
                }
                $leaseContract = $query->limit(1)->one();
                if ($leaseContract->getContractDetails()->count() != 1) {
                    throw new \Exception('対象詳細契約が見つからないか、複数存在しています。');
                }
                $contractDetail = $leaseContract->contractDetails[0];
                $contract = $leaseContract->customer->clientContract;

                echo "{$contractNumber}[$contractDetail->contract_detail_id]::updating..\n";

                //登録済み回収情報を全削除
                Yii::$app->db->createCommand()->delete('repayment', ['contract_detail_id' => $contractDetail->contract_detail_id])->execute();

                $query = ImportRepaymentUpdate::find()
                    ->where(['import_contract_number' => $contractNumber]);
                foreach($query->each() as $repaymentRow) {
                    $repaymentType = RepaymentType::findOne(['type' => $repaymentRow->repayment_type, 'removed' => null]);
                    if (!$repaymentType) {
                        $repaymentType = new RepaymentType([
                            'type' => $repaymentRow->repayment_type,
                        ]);
                        $repaymentType->save();
                        //throw new Exception('回収方法が特定出来ません。登録情報をご確認ください。');
                    }
                    $repaymentProcessed = new \DateTime((new \DateTime($repaymentRow->processed))->format('Y-m-01'));
                    $repaymentRegistered = new \DateTime((new \DateTime($repaymentRow->registered))->format('Y-m-01'));
                    $term = ($repaymentProcessed->format('Ym') < $repaymentRegistered->format('Ym')) ? $repaymentRegistered : $repaymentProcessed;
                    if ($contract->repaymentPattern->target_month == 'next') {
                        $term->modify('-1 month');
                    }

                    $monthlyCharge = MonthlyCharge::find()->alias('mc')
                        ->leftJoin('repayment r', 'mc.monthly_charge_id=r.monthly_charge_id')
                        ->where(['and',
                            ['mc.contract_detail_id' => $contractDetail->contract_detail_id],
                            ['DATE_FORMAT(mc.term, "%Y%m")' => $term->format('Ym')],
                            ['r.repayment_id' => null],
                        ])->limit(1)->one();
                    if (!$monthlyCharge) {
                        $monthlyCharge = MonthlyCharge::find()->alias('mc')
                            ->leftJoin('repayment r', 'mc.monthly_charge_id=r.monthly_charge_id')
                            ->where(['and',
                                ['mc.contract_detail_id' => $contractDetail->contract_detail_id],
                                ['>', 'mc.term', $term->format('Y-m-01')],
                                ['r.repayment_id' => null],
                            ])->orderBy(['monthly_charge_id' => SORT_ASC])->limit(1)->one();
                        if ($monthlyCharge) {
                            do {
                                $monthlyCharge->slideTerm(-1);
                                $monthlyCharge->refresh();
                            } while((new \DateTime($monthlyCharge->term))->format('Ym') > $term->format('Ym'));
                        }
                        else {
                            echo "=";
                            continue;
                        }
                    }
                    $repayment = new Repayment([
                        'scenario' => 'import',
                        'contract_detail_id' => $contractDetail->contract_detail_id,
                        'monthly_charge_id' => $monthlyCharge->monthly_charge_id,
                        'repayment_type_id' => $repaymentType->repayment_type_id,
                        'repayment_amount' => $repaymentRow->repayment_amount,
                        'processed' => $repaymentRow->processed,
                        'registered' => $repaymentRow->registered,
                    ]);
                    if (!$repayment->save()) {
                        echo "[SKIP:{$repayment->repaymentType->type}]";
                    }
                    else {
                        $registered['repayment'] += 1;
                    }
                    echo "*";

                }
            }
            echo "\n";

            //支払情報取り込み
            //import_contract_numberから対象となるcontractDetailを定義
            $contractNumbers = ImportPaymentUpdate::find()
                ->select(['import_contract_number'])
                ->distinct(true)
                ->column();
            foreach($contractNumbers as $contractNumber) {
                $query = LeaseContract::find()->where(['code_search' => $contractNumber]);
                if ($query->count() != 1) {
                    throw new \Exception('対象契約が見つからないか、複数存在しています。');
                }
                $leaseContract = $query->limit(1)->one();
                if ($leaseContract->getContractDetails()->count() != 1) {
                    throw new \Exception('対象詳細契約が見つからないか、複数存在しています。');
                }
                $contractDetail = $leaseContract->contractDetails[0];

                echo "{$contractNumber}[$contractDetail->contract_detail_id]::updating..\n";

                //登録済み支払情報を全削除
                Yii::$app->db->createCommand()->delete('lease_payment', ['contract_detail_id' => $contractDetail->contract_detail_id])->execute();

                $query = ImportPaymentUpdate::find()
                    ->where(['import_contract_number' => $contractNumber]);
                foreach($query->each() as $paymentRow) {
                    $monthlyPayment = MonthlyPayment::find()->alias('mp')
                        ->leftJoin('lease_payment lp', 'mp.monthly_payment_id=lp.monthly_payment_id')
                        ->where([
                            'mp.contract_detail_id' => $contractDetail->contract_detail_id,
                            'DATE_FORMAT(mp.term, "%Y%m")'=>(new \DateTime($paymentRow->processed))->format('Ym'),
                            'lp.lease_payment_id' => null,
                        ])->limit(1)->one();
                    if (!$monthlyPayment) {
                        echo "=";
                        continue;
                    }
                    $payment = new LeasePayment([
                        'contract_detail_id' => $contractDetail->contract_detail_id,
                        'monthly_payment_id' => $monthlyPayment->monthly_payment_id,
                        'payment_amount' => $paymentRow->payment_amount,
                        'processed' => $paymentRow->processed,
                        'registered' => $paymentRow->registered
                    ]);
                    if (!$payment->save()) {
                        throw new Exception('支払情報が登録出来ません。' . VarDumper::dumpAsString($payment->firstErrors,10,0));
                    }
                    else {
                        $registered['payment'] += 1;
                    }
                    echo "~";
                }
                echo "\n";
            }

            $transaction->commit();
            return $registered;
        } catch(\Throwable $e) {
            $transaction->rollback();
            echo $e->getMessage();
            VarDumper::dump([$e->getTraceAsString(), $e]);
        }
        return [
            'repayment' => 0,
            'payment' => 0,
        ];
    }
}