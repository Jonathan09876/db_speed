<?php

namespace app\controllers;

use app\models\AccountTransferAgency;
use app\models\AccountTransferAgencySearch;
use app\models\BankAccount;
use app\models\CdMonthlyChargeSearch;
use app\models\ClientContract;
use app\models\ClientCorporation;
use app\models\ClientCorporationSearch;
use app\models\CollectionCell;
use app\models\ContractDetail;
use app\models\ContractPattern;
use app\models\ContractPatternSearch;
use app\models\Customer;
use app\models\CustomerSearch;
use app\models\LeaseContract;
use app\models\LeaseContractSearch;
use app\models\LeaseContractStatus;
use app\models\LeaseContractStatusType;
use app\models\LeaseContractStatusTypeSearch;
use app\models\LeaseServicer;
use app\models\LeaseServicerSearch;
use app\models\LeaseTarget;
use app\models\Location;
use app\models\MailAddress;
use app\models\MonthlyCharge;
use app\models\MonthlyChargeSearch;
use app\models\MonthlyChargeSearch2;
use app\models\Phone;
use app\models\Repayment;
use app\models\RepaymentByCustomerForm;
use app\models\RepaymentPattern;
use app\models\RepaymentPatternSearch;
use app\models\RepaymentSearch;
use app\models\RepaymentType;
use app\models\RepaymentTypeSearch;
use app\models\SalesPerson;
use app\models\SalesPersonSearch;
use app\models\SetClientCorporationForm;
use app\models\TargetTermMonthlyChargeStored;
use app\models\TargetTermMonthlyChargeStoredSearch;
use app\models\TaxApplication;
use app\models\TaxApplicationSearch;
use app\models\Term;
use app\models\ZenginMaster;
use Yii;
use app\models\LoginForm;
use yii\base\Model;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\db\Exception;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\VarDumper;
use yii\web\Response;
use yii\web\UrlManager;

class AasController extends \yii\web\Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        $this->layout = 'login-main';
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionContracts()
    {
        return $this->render('under-construction');
    }

    public function actionMasters()
    {
        return $this->render('masters');
    }

    public function actionCollections()
    {
        return $this->render('under-construction');
    }

    public function actionConfigurations()
    {
        return $this->render('under-construction');
    }

    /** 回収先マスタ関連  **/

    /**
     * @return string
     */
    public function actionAccountTransferAgencies()
    {
        $searchModel = new AccountTransferAgencySearch();
        if (Yii::$app->request->isPost) {
            $searchModel->load(Yii::$app->request->post());
        }
        $dataProvider = $searchModel->search();
        return $this->render('account-transfer-agencies', compact("searchModel", "dataProvider"));
    }

    /**
     * @param $id
     * @return string|Response
     */
    public function actionAccountTransferAgency($id = null)
    {
        if (isset($id)) {
            $model = AccountTransferAgency::findOne($id);
        } else {
            $model = new AccountTransferAgency();
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->save();
            return $this->redirect(['/aas/account-transfer-agencies']);
        }
        return $this->render('account-transfer-agency', compact("model"));
    }

    /**
     * @param $id
     * @return Response
     */
    public function actionRemoveAccountTransferAgency($id)
    {
        $model = AccountTransferAgency::findOne($id);
        if ($model) {
            $model->removed = date('Y-m-d H:i:s');
            $model->save();
        }
        return $this->redirect(['/aas/account-transfer-agencies']);
    }

    /** リース会社マスタ関連 **/

    public function actionLeaseServicers()
    {
        $searchModel = new LeaseServicerSearch();
        if (Yii::$app->request->isPost) {
            $searchModel->load(Yii::$app->request->post());
        }
        $dataProvider = $searchModel->search();
        return $this->render('lease-servicers', compact("searchModel", "dataProvider"));
    }

    public function actionLeaseServicer($id = null)
    {
        if (isset($id)) {
            $model = LeaseServicer::findOne($id);
        } else {
            $model = new LeaseServicer();
        }
        if ($model->isNewRecord) {
            $bankModel = new BankAccount([
                'scenario' => 'lease-servicer'
            ]);
        } else {
            $bankModel = $model->bankAccount ? $model->bankAccount : new BankAccount([
                'scenario' => 'lease-servicer'
            ]);;
        }
        if ($bankModel->load(Yii::$app->request->post())) {
            $bankModel->scenario = 'default';
            if ($bankModel->validate()) {
                $bankModel->save();
                if ($model->isNewRecord) {
                    $model->bank_account_id = $bankModel->bank_account_id;
                }
            }
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->save();
            return $this->redirect(['/aas/lease-servicers']);
        }
        return $this->render('lease-servicer', compact("model", "bankModel"));
    }

    public function actionRemoveLeaseServicer($id)
    {
        $model = LeaseServicer::findOne($id);
        if ($model) {
            $model->removed = date('Y-m-d H:i:s');
            $model->save();
        }
        return $this->redirect(['/aas/lease-servicers']);
    }

    public function actionGetBankName($name)
    {
        $name = mb_convert_kana(mb_strtoupper($name), 'R');
        $query = ZenginMaster::find()->where(['and', ['division' => 1], ['like', 'name', $name]]);
        return $this->asJson($query->asArray()->all());
    }

    public function actionGetBankCode($code)
    {
        $code = mb_convert_kana($code, 'as');
        $query = ZenginMaster::find()->where(['and', ['division' => 1], ['like', 'bank_code', $code]]);
        return $this->asJson($query->asArray()->all());
    }

    public function actionGetBranchName($code, $name)
    {
        $name = mb_convert_kana(mb_strtoupper($name), 'R');
        $query = ZenginMaster::find()->where(['and', ['division' => 2], ['bank_code' => $code], ['like', 'name', $name]]);
        return $this->asJson($query->asArray()->all());
    }

    public function actionGetBranchCode($code, $code2)
    {
        $code = mb_convert_kana($code, 'as');
        $query = ZenginMaster::find()->where(['and', ['division' => 2], ['bank_code' => $code], ['like', 'branch_code', $code2]]);
        return $this->asJson($query->asArray()->all());
    }

    public function actionCustomers()
    {
        $searchModel = new CustomerSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->post());
        return $this->render('customers', compact("searchModel", "dataProvider"));
    }

    public function actionCustomer($id = null)
    {
        $errors = [];
        if (isset($id)) {
            $model = Customer::findOne($id);
        } else {
            $model = new Customer();
        }
        if ($model->isNewRecord) {
            $contractModel = new ClientContract();
            $bankModel = new BankAccount(['scenario' => 'customer']);
            $locationModel = new Location();
        } else {
            $contractModel = $model->clientContract;
            $bankModel = $model->bankAccount;
            $locationModel = $model->location;
        }
        if (Yii::$app->request->isPost) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($contractModel->load(Yii::$app->request->post())) {
                    if ($contractModel->validate()) {
                        $contractModel->save();
                        if ($model->isNewRecord) {
                            $model->client_contract_id = $contractModel->client_contract_id;
                        }
                    }
                }
                if ($bankModel->load(Yii::$app->request->post())) {
                    if ($bankModel->validate()) {
                        $bankModel->save();
                        if ($model->isNewRecord) {
                            $model->bank_account_id = $bankModel->bank_account_id;
                        }
                    }
                }
                if ($locationModel->load(Yii::$app->request->post())) {
                    if ($locationModel->validate()) {
                        $locationModel->save();
                        if ($model->isNewRecord) {
                            $model->location_id = $locationModel->location_id;
                        }
                    }
                }
                if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                    $model->save();
                    if ($postedPhones = Yii::$app->request->post('Phone')) {
                        foreach ($postedPhones as $postedPhone) {
                            if (!empty($postedPhone['number'])) {
                                $phoneModel = isset($postedPhone['phone_id']) ? Phone::findOne($postedPhone['phone_id']) : new Phone(['customer_id' => $model->customer_id]);
                                if ($phoneModel->isNewRecord) {
                                    $model->phoneModels[] = $phoneModel;
                                }
                                $phoneModel->setAttributes($postedPhone);
                                if ($phoneModel->validate()) {
                                    $phoneModel->save();
                                } else {
                                    throw new Exception('電話番号の内容に問題があります。');
                                }
                            }
                        }
                    }
                    if ($postedMailAddresses = Yii::$app->request->post('MailAddress')) {
                        foreach ($postedMailAddresses as $postedMailAddress) {
                            if (!empty($postedMailAddress['mail_address'])) {
                                $mailAddressModel = isset($postedMailAddress['mail_address_id']) ? MailAddress::findOne($postedMailAddress['mail_address_id']) : new MailAddress(['customer_id' => $model->customer_id]);
                                if ($mailAddressModel->isNewRecord) {
                                    $model->mailAddressModels[] = $mailAddressModel;
                                }
                                $mailAddressModel->setAttributes($postedMailAddress);
                                if ($mailAddressModel->validate()) {
                                    $mailAddressModel->save();
                                } else {
                                    throw new Exception('メールアドレスの内容に問題があります。');
                                }
                            }
                        }
                    }
                    $transaction->commit();
                    Yii::$app->session->setFlash('register-customer', "「{$model->name}」様を登録しました。");
                    return $this->redirect(['/aas/customers']);
                }
            } catch (\Throwable $e) {
                $transaction->rollBack();
                $errors = [$e->getMessage()];
            }
        }
        return $this->render('customer', compact("model", "contractModel", "bankModel", "locationModel", "errors"));
    }

    public function actionRemoveCustomer($id)
    {

    }

    public function actionSetClientCorporation($id = null)
    {
        $session = Yii::$app->session;
        if (!empty($id)) {
            $session['current_client_corporation_id'] = (int)$id;
        } else {
            $session->remove('current_client_corporation_id');
        }
        return $this->asJson(['value' => $session['current_client_corporation_id']]);
    }

    public function actionGetCustomers($q = null)
    {
        $session = Yii::$app->session;
        $client_corporation_id = $session['current_client_corporation_id'] ?? null;
        $query = Customer::find()->alias('c')
            ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
            ->where(['c.removed' => null])
            ->andWhere(['cc.client_corporation_id' => $client_corporation_id]);
        if (!is_null($q)) {
            $query->andWhere(['like', 'c.customer_code', $q]);
        }
        $result = $query->select(['customer_id', 'customer_code', 'name', 'bank_account_id'])->asArray()->all();
        return $this->asJson($result);
    }

    public function actionGetCustomersByName($q = null)
    {
        $session = Yii::$app->session;
        $client_corporation_id = $session['current_client_corporation_id'] ?? null;
        $query = Customer::find()->alias('c')
            ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
            ->where(['c.removed' => null])
            ->andWhere(['cc.client_corporation_id' => $client_corporation_id]);
        if (!is_null($q)) {
            $query->andWhere(['like', 'name', $q]);
        }
        $result = $query->select(['customer_id', 'customer_code', 'name', 'bank_account_id'])->asArray()->all();
        return $this->asJson($result);
    }

    public function actionSetClientCorporations()
    {
        $session = Yii::$app->session;
        $model = new MonthlyChargeSearch([
            'target_term' => (new \DateTime())->format('Y年n月'),
        ]);
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->updateSession();
        } else {
            return $this->asJson($model->errors);
        }
        return $this->asJson(['value' => $session['customer-client-corporations']]);
    }

    public function actionGetCustomerInfo($q = null)
    {
        $session = Yii::$app->session;
        $client_corporation_id = $session['customer-client-corporations'] ?? [];
        $query = Customer::find()->alias('c')
            ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
            ->where(['c.removed' => null])
            ->andWhere(['cc.client_corporation_id' => $client_corporation_id]);
        if (!is_null($q)) {
            $query->andWhere(['like', 'c.customer_code', $q]);
        }
        $result = $query->select(['customer_id', 'customer_code', 'name'])->asArray()->all();
        return $this->asJson($result);
    }

    public function actionGetCustomerInfoByName($q = null)
    {
        $session = Yii::$app->session;
        $client_corporation_id = $session['customer-client-corporations'] ?? [];
        $query = Customer::find()->alias('c')
            ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
            ->where(['c.removed' => null])
            ->andWhere(['cc.client_corporation_id' => $client_corporation_id]);
        if (!is_null($q)) {
            $query->andWhere(['like', 'name', $q]);
        }
        $result = $query->select(['customer_id', 'customer_code', 'name'])->asArray()->all();
        return $this->asJson($result);
    }

    public function actionGetBankAccount($id)
    {
        return (string)BankAccount::findOne($id);
    }

    public function actionRepaymentPatterns()
    {
        $searchModel = new RepaymentPatternSearch();
        if (Yii::$app->request->isPost) {
            $searchModel->load(Yii::$app->request->post());
        }
        $dataProvider = $searchModel->search();
        return $this->render('repayment-patterns', compact("searchModel", "dataProvider"));
    }

    public function actionRepaymentPattern($id = null)
    {
        if (isset($id)) {
            $model = RepaymentPattern::findOne($id);
        } else {
            $model = new RepaymentPattern();
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->save();
            return $this->redirect(['/aas/repayment-patterns']);
        }
        return $this->render('repayment-pattern', compact("model"));
    }

    public function actionRemoveRepaymentPattern($id)
    {
        $model = RepaymentPattern::findOne($id);
        if ($model) {
            $model->removed = date('Y-m-d H:i:s');
            $model->save();
        }
        return $this->redirect(['/aas/repayment-patterns']);
    }

    public function actionTaxApplications()
    {
        $searchModel = new TaxApplicationSearch();
        if (Yii::$app->request->isPost) {
            $searchModel->load(Yii::$app->request->post());
        }
        $dataProvider = $searchModel->search();
        return $this->render('tax-applications', compact("searchModel", "dataProvider"));
    }

    public function actionTaxApplication($id = null)
    {
        if (isset($id)) {
            $model = TaxApplication::findOne($id);
        } else {
            $model = new TaxApplication();
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->save();
            return $this->redirect(['/aas/tax-applications']);
        }
        return $this->render('tax-application', compact("model"));
    }

    public function actionRemoveTaxApplication($id)
    {
        $model = TaxApplication::findOne($id);
        if ($model) {
            $model->removed = date('Y-m-d H:i:s');
            $model->save();
        }
        return $this->redirect(['/aas/tax-applications']);
    }

    public function actionRankupTaxApplication($id)
    {
        $model = TaxApplication::findOne($id);
        $target = TaxApplication::findOne(['disp_order' => $model->disp_order - 1]);
        $model->disp_order -= 1;
        $target->disp_order += 1;
        $model->save();
        $target->save();
        return $this->redirect(['/aas/tax-applications']);
    }

    public function actionRankdownTaxApplication($id)
    {
        $model = TaxApplication::findOne($id);
        $target = TaxApplication::findOne(['disp_order' => $model->disp_order + 1]);
        $model->disp_order += 1;
        $target->disp_order -= 1;
        $model->save();
        $target->save();
        return $this->redirect(['/aas/tax-applications']);
    }

    public function actionClientCorporations()
    {
        $searchModel = new ClientCorporationSearch();
        if (Yii::$app->request->isPost) {
            $searchModel->load(Yii::$app->request->post());
        }
        $dataProvider = $searchModel->search();
        return $this->render('client-corporations', compact("searchModel", "dataProvider"));
    }

    public function actionClientCorporation($id = null)
    {
        if (isset($id)) {
            $model = ClientCorporation::findOne($id);
        } else {
            $model = new ClientCorporation();
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->save();
            return $this->redirect(['/aas/client-corporations']);
        }
        return $this->render('client-corporation', compact("model"));
    }

    public function actionRemoveClientCorporation($id)
    {
        $model = ClientCorporation::findOne($id);
        if ($model) {
            $model->removed = date('Y-m-d H:i:s');
            $model->save();
        }
        return $this->redirect(['/aas/client-corporations']);
    }

    public function actionContractPatterns()
    {
        $searchModel = new ContractPatternSearch();
        if (Yii::$app->request->isPost) {
            $searchModel->load(Yii::$app->request->post());
        }
        $dataProvider = $searchModel->search();
        return $this->render('contract-patterns', compact("searchModel", "dataProvider"));
    }

    public function actionContractPattern($id = null)
    {
        if (isset($id)) {
            $model = ContractPattern::findOne($id);
        } else {
            $model = new ContractPattern();
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->save();
            return $this->redirect(['/aas/contract-patterns']);
        }
        return $this->render('contract-pattern', compact("model"));
    }

    public function actionRemoveContractPattern($id)
    {
        $model = ContractPattern::findOne($id);
        if ($model) {
            $model->removed = date('Y-m-d H:i:s');
            $model->save();
        }
        return $this->redirect(['/aas/contract-patterns']);
    }

    public function actionSalesPersons()
    {
        $searchModel = new SalesPersonSearch();
        if (Yii::$app->request->isPost) {
            $searchModel->load(Yii::$app->request->post());
        }
        $dataProvider = $searchModel->search();
        return $this->render('sales-persons', compact("searchModel", "dataProvider"));
    }

    public function actionSalesPerson($id = null)
    {
        if (isset($id)) {
            $model = SalesPerson::findOne($id);
        } else {
            $model = new SalesPerson();
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->save();
            return $this->redirect(['/aas/sales-persons']);
        }
        return $this->render('sales-person', compact("model"));
    }

    public function actionRemoveSalesPerson($id)
    {
        $model = SalesPerson::findOne($id);
        if ($model) {
            $model->removed = date('Y-m-d H:i:s');
            $model->save();
        }
        return $this->redirect(['/aas/sales-persons']);
    }

    public function actionLeaseContractStatuses()
    {
        $searchModel = new LeaseContractStatusTypeSearch();
        if (Yii::$app->request->isPost) {
            $searchModel->load(Yii::$app->request->post());
        }
        $dataProvider = $searchModel->search(Yii::$app->request->post());
        return $this->render('lease-contract-statuses', compact("searchModel", "dataProvider"));
    }

    public function actionLeaseContractStatus($id = null)
    {
        if (isset($id)) {
            $model = LeaseContractStatusType::findOne($id);
        } else {
            $model = new LeaseContractStatusType();
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->save();
            return $this->redirect(['/aas/lease-contract-statuses']);
        }
        return $this->render('lease-contract-status', compact("model"));
    }

    public function actionRemoveLeaseContractStatus($id)
    {
        $model = LeaseContractStatusType::findOne($id);
        if ($model) {
            if (!$model->hasLeaseContractStatus()) {
                $model->removed = date('Y-m-d H:i:s');
                $model->save();
            }
        }
        return $this->redirect(['/aas/lease-contract-statuses']);
    }

    public function actionLeaseContracts()
    {
        $searchModel = new LeaseContractSearch();
        $customerSearchModel = new CustomerSearch();
        $session = Yii::$app->session;
        if (Yii::$app->request->isPost) {
            $params = Yii::$app->request->post();
            $session['lease-contracts-params'] = $params;
        } else {
            $params = Yii::$app->request->isAjax || Yii::$app->request->get('page') ? ($session['lease-contracts-params'] ?? []) : [];
        }
        $dataProvider = $searchModel->search($params);
        return $this->render('lease-contracts-new', compact("searchModel", "customerSearchModel", "dataProvider"));
    }

    public function actionUpdateContractPattern($id)
    {
        $patterns = ContractPattern::getContractPatterns($id);
        $model = new LeaseContractSearch();
        return Html::activeDropDownList($model, 'contract_pattern_id', $patterns, ['class' => 'form-control', 'prompt' => '契約マスタ選択']);
    }


    public function actionRankupLeaseContract($id)
    {
        $model = LeaseContract::findOne($id);
        $target = LeaseContract::findOne(['disp_order' => $model->disp_order - 1]);
        if ($model && $target) {
            $model->disp_order -= 1;
            $target->disp_order += 1;
            $model->save();
            $target->save();
            return $this->asJson(['success' => true]);
        }
        return $this->asJson(['success' => false]);
    }

    public function actionRankdownLeaseContract($id)
    {
        $model = LeaseContract::findOne($id);
        $target = LeaseContract::findOne(['disp_order' => $model->disp_order + 1]);
        if ($model && $target) {
            $model->disp_order += 1;
            $target->disp_order -= 1;
            $model->save();
            $target->save();
            return $this->asJson(['success' => true]);
        }
        return $this->asJson(['success' => false]);
    }

    public function actionLeaseContractRecent($id = null)
    {
        $errors = [];
        $session = Yii::$app->session;
        $client_corporation_id = $session['current_client_corporation_id'] ?? null;
        if (isset($id)) {
            $model = LeaseContract::findOne($id);
            $targetModel = $model->leaseTarget;
        } else {
            $model = new LeaseContract();
            $targetModel = new LeaseTarget();
        }
        $details = $model->isNewRecord ? [
            new ContractDetail()
        ] : $model->contractDetails;
        if (Yii::$app->request->isPost) {
            $model->scenario =
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $hasErrors = false;
                if ($targetModel->load(Yii::$app->request->post()) && $targetModel->validate()) {
                    $targetModel->save();
                    $model->lease_target_id = $targetModel->lease_target_id;
                } else {
                    $hasErrors = true;
                    $errors[] = $targetModel->errors;
                }
                if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                    $model->save();
                    $contractDetailCount = count(Yii::$app->request->post('ContractDetail', []));
                    if ($contractDetailCount > count($details)) {
                        for ($i = count($details); $i < $contractDetailCount; $i++) {
                            $details[] = new ContractDetail();
                        }
                    }
                    if (Model::loadMultiple($details, Yii::$app->request->post())) {
                        foreach ($details as $detail) {
                            $detail->lease_contract_id = $model->lease_contract_id;
                            if ($detail->validate()) {
                                $detail->save();
                            } else {
                                $hasErrors = true;
                                $errors[] = $detail->errors;
                            }
                        }
                    }
                } else {
                    $hasErrors = true;
                    $errors[] = $model->errors;
                    $contractDetailCount = count(Yii::$app->request->post('ContractDetail', []));
                    if ($contractDetailCount > count($details)) {
                        for ($i = count($details); $i < $contractDetailCount; $i++) {
                            $details[] = new ContractDetail();
                        }
                    }
                    if (Model::loadMultiple($details, Yii::$app->request->post())) {
                        foreach ($details as $detail) {
                            $detail->lease_contract_id = $model->lease_contract_id;
                            if (!$detail->validate()) {
                                $hasErrors = true;
                                $errors[] = $detail->errors;
                            }
                        }
                    }
                }
                if (!$hasErrors) {
                    $transaction->commit();
                    return $this->redirect(['/aas/lease-contracts']);
                }
            } catch (\Throwable $e) {
                //return VarDumper::dumpAsString($e, 10, 1);
                $transaction->rollBack();
            }
        }
        return $this->render('lease-contract', compact("model", "targetModel", "details"));
    }

    public function actionLeaseContract($id = null, $dc = null)
    {
        $errors = [];
        $session = Yii::$app->session;
        $client_corporation_id = $session['current_client_corporation_id'] ?? null;
        if (isset($id)) {
            $model = LeaseContract::findOne($id);
            $targetModel = $model->leaseTarget;
        } else if (isset($dc)) {
            $originModel = LeaseContract::findOne($dc);
            $originTargetModel = LeaseTarget::findOne($originModel->lease_target_id);
            $attributes = $originModel->attributes;
            unset($attributes['lease_contract_id']);
            /*
            $attributes['contract_sub_code'] = LeaseContract::find()->where([
                    'contract_pattern_id' => $originModel->contract_pattern_id,
                    'contract_number' => $originModel->contract_number,
                    'contract_code' => $originModel->contract_code
                ])->max('CAST(contract_sub_code AS UNSIGNED)') + 1;
            */
            $model = new LeaseContract($attributes);
            $customer = $originModel->customer;
            $model->customer_client_corporation = $customer->clientContract->client_corporation_id;
            $model->customer_code = $customer->customer_code;
            $model->customer_name = $customer->name;
            $targetAttributes = $originTargetModel->getAttributes();
            unset($targetAttributes['lease_target_id']);
            $targetModel = new LeaseTarget($targetAttributes);
        } else {
            $model = new LeaseContract();
            $targetModel = new LeaseTarget();
        }
        if (isset($dc)) {
            $details = [];
            $originModel = LeaseContract::findOne($dc);
            foreach ($originModel->contractDetails as $detail) {
                $attributes = $detail->attributes;
                unset($attributes['contracct_detail_id']);
                unset($attributes['lease_contract_id']);
                $details[] = new ContractDetail($attributes);
            }
        } else {
            $details = $model->isNewRecord ? [
                new ContractDetail([
                    'contract_type' => 'ordinary',
                    'fraction_processing_pattern' => 'floor',
                    'advance_repayment_count' => 0,
                ])
            ] : $model->contractDetails;
        }
        if (Yii::$app->request->isPost) {
            $posted = Yii::$app->request->post();
            $model->scenario = $model->isNewRecord ? LeaseContract::SCENARIO_REGISTER : LeaseContract::SCENARIO_DEFAULT;
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $hasErrors = false;
                if ($targetModel->load(Yii::$app->request->post()) && $targetModel->validate()) {
                    $targetModel->save();
                    $model->lease_target_id = $targetModel->lease_target_id;
                } else {
                    $hasErrors = 1;
                    $errors[] = $targetModel->errors;
                }
                if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                    $model->save();
                    $contractDetailCount = count(Yii::$app->request->post('ContractDetail', []));
                    if ($contractDetailCount > count($details)) {
                        for ($i = count($details); $i < $contractDetailCount; $i++) {
                            $details[] = new ContractDetail();
                        }
                    }
                    if (Model::loadMultiple($details, Yii::$app->request->post())) {
                        foreach ($details as $detail) {
                            $detail->lease_contract_id = $model->lease_contract_id;
                            $detail->regenerateMonthlyChargesPayments = $model->regenerateMonthlyChargesPayments;
                            $detail->regenerateMonthlyCharges = $model->regenerateMonthlyCharges;
                            $detail->regenerateMonthlyPayments = $model->regenerateMonthlyPayments;
                            if ($detail->validate()) {
                                if (!$detail->save()) {
                                    $hasErrors = 5;
                                    $errors[] = $detail->errors;
                                }
                            } else {
                                $hasErrors = 2;
                                $errors[] = $detail->errors;
                            }
                        }
                    }
                } else {
                    $hasErrors = 3;
                    $errors[] = $model->errors;
                    $contractDetailCount = count(Yii::$app->request->post('ContractDetail', []));
                    if ($contractDetailCount > count($details)) {
                        for ($i = count($details); $i < $contractDetailCount; $i++) {
                            $details[] = new ContractDetail();
                        }
                    }
                    if (Model::loadMultiple($details, Yii::$app->request->post())) {
                        foreach ($details as $detail) {
                            $detail->lease_contract_id = $model->lease_contract_id;
                            $detail->regenerateMonthlyChargesPayments = $model->regenerateMonthlyChargesPayments;
                            $detail->regenerateMonthlyCharges = $model->regenerateMonthlyCharges;
                            $detail->regenerateMonthlyPayments = $model->regenerateMonthlyPayments;
                            if (!$detail->validate()) {
                                $hasErrors = 4;
                                $errors[] = $detail->errors;
                            }
                        }
                    }
                }
                if (!$hasErrors) {
                    $transaction->commit();
                    if ($model->scenario == LeaseContract::SCENARIO_REGISTER) {
                        $currentStatus = !empty($posted['LeaseContract']['current_status']) ? $posted['LeaseContract']['current_status'] : 1;
                        LeaseContractStatus::register($model->lease_contract_id, $currentStatus);
                        $model->current_status = $currentStatus;
                    }
                    Yii::$app->session->setFlash('契約情報を登録しました。');
                    $model->updateOrder();
                    return $this->redirect(['/aas/lease-contract', 'id' => $model->lease_contract_id]);
                } else {
                    $transaction->rollBack();
                    //return VarDumper::dumpAsString(compact("hasErrors", "errors"), 10, 1);
                }
            } catch (\Throwable $e) {
                $transaction->rollBack();
                return VarDumper::dumpAsString($e, 10, 1);
            }
        }
        return $this->render('lease-contract-chart', compact("model", "targetModel", "details"));
    }

    public function actionUpdateContractPatterns($id)
    {
        $model = new LeaseContract();
        return Html::activeDropDownList($model, 'contract_pattern_id', LeaseContract::getContractPatterns($id), ['class' => 'form-control form-select' . ($model->hasErrors('contract_number_check') || $model->hasErrors('contract_pattern_id') ? ' is-invalid' : ''), 'prompt' => '契約マスタ選択']) .
            Html::error($model, 'contract_pattern_id');


    }

    public function actionRemoveLeaseContract($id)
    {
        $model = LeaseContract::findOne($id);
        if ($model) {
            $model->remove();
            return $this->asJson(['success' => true]);
        }
        return $this->asJson(['success' => false]);
    }

    public function actionUpdateLeaseContractStatus($id, $status_type_id = null)
    {
        $model = LeaseContract::findOne($id);
        if (isset($status_type_id)) {
            LeaseContractStatus::register($id, $status_type_id);
            $model->current_status = $status_type_id;
        }
        return $this->asJson(['success' => true, 'tag' => Html::activeDropDownList($model, 'current_status', \app\models\LeaseContractStatusType::getTypes(), ['class' => 'form-control form-select', 'prompt' => '', 'data-lcid' => $model->lease_contract_id])]);
    }

    public function actionIncrementMonthlyCharge($id)
    {
        $model = ContractDetail::findOne($id);
        $lastMonthlyCharge = MonthlyCharge::find()->alias('mc')
            ->where(['mc.contract_detail_id' => $id])
            ->orderBy(['monthly_charge_id' => SORT_DESC])->limit(1)->one();
        $lastMonthlyChargeTerm = new \DateTime($lastMonthlyCharge->term);
        $incrementChargeTerm = (clone $lastMonthlyChargeTerm)->modify('+1 month');
        $transfer_date = $model->leaseContract->customer->clientContract->repaymentPattern->transfer_date;
        $format = $transfer_date == 31 ? 'Y-m-t' : "Y-m-{$transfer_date}";
        $instance = new MonthlyCharge([
            'contract_detail_id' => $id,
            'term' => $incrementChargeTerm->format('Y-m-d'),
            'transfer_date' => $incrementChargeTerm->format($format),
            'charge_amount' => $model->monthly_charge,
            'registered' => date('Y-m-d H:i:s'),
        ]);
        return $this->asJson(['success' => $instance->save()]);
    }

    public function actionLeaseContractOperationRecent($span = null)
    {
        $session = Yii::$app->session;
        $session['filter-name'] = null;
        $session['filter-code'] = null;
        $searchModel = new MonthlyChargeSearch2([
            'target_term' => (new \DateTime())->format('Y年m月'),
            'client_corporation_id' => Yii::$app->user->identity->client_corporation_id,
        ]);
        if (isset($span)) {
            $searchModel->span = $span;
        }
        $searchModel->updateSession();
        $params = Yii::$app->request->isPost ? Yii::$app->request->post() : (Yii::$app->request->get('page') || Yii::$app->request->get('sort') || Yii::$app->request->get('span') || Yii::$app->request->isAjax ? $session['monthly_charge_search_params'] : []);
        $dataProvider = $searchModel->search($params);
        if (!!$searchModel->store_collection_data) {
            TargetTermMonthlyChargeStored::register($params);
            return $this->redirect(['/aas/stored-collection-data']);
        }
        if (!!$searchModel->credit_debt_collection_by_agency) {
            return $this->render('credit-debt-collection-by-agency', compact("searchModel", "dataProvider"));
        }
        if (!!$searchModel->credit_debt_collection_by_customer) {
            return $this->render('credit-debt-collection-by-customer', compact("searchModel", "dataProvider"));
        }
        if (!!$searchModel->calc_collection_data) {
            return $this->render('calc-collection-data', compact("searchModel", "dataProvider"));
        } else {
            return $this->render('lease-contract-operation-new', compact("searchModel", "dataProvider"));
        }
    }

    public function actionLeaseContractOperation($span = null)
    {
        set_time_limit(300);
        $session = Yii::$app->session;
        $session['filter-name'] = null;
        $session['filter-code'] = null;
        $current = new \DateTime();
        $searchModel = new MonthlyChargeSearch2([
            'target_term_year' => $current->format('Y'),
            'target_term' => $current->format('Y年m月'),
            'client_corporation_id' => Yii::$app->user->identity->client_corporation_id,
        ]);
        if (isset($span)) {
            $searchModel->span = $span;
        }
        $searchModel->updateSession();
        $params = Yii::$app->request->isPost ? Yii::$app->request->post() : (Yii::$app->request->get('page') || Yii::$app->request->get('sort') || Yii::$app->request->get('span') || Yii::$app->request->isAjax ? $session['monthly_charge_search_params'] : []);
        $dataProvider = $searchModel->search($params);
        if (!!$searchModel->store_collection_data) {
            TargetTermMonthlyChargeStored::register($params);
            return $this->redirect(['/aas/stored-collection-data']);
        }
        if (!!$searchModel->credit_debt_collection_by_agency) {
            return $this->render('credit-debt-collection-by-agency', compact("searchModel", "dataProvider"));
        }
        if (!!$searchModel->credit_debt_collection_by_customer) {
            return $this->render('credit-debt-collection-by-customer', compact("searchModel", "dataProvider"));
        }
        if (!!$searchModel->calc_collection_data) {
            return $this->render('calc-collection-data', compact("searchModel", "dataProvider"));
        } else {
            return $this->render('lease-contract-operation-latest', compact("searchModel", "dataProvider"));
        }
    }

    public function actionCalcCollectionData()
    {
        $session = Yii::$app->session;
        $session['filter-name'] = null;
        $session['filter-code'] = null;
        $searchModel = new MonthlyChargeSearch([
            'target_term' => (new \DateTime())->format('Y年m月'),
            'client_corporation_id' => Yii::$app->user->identity->client_corporation_id,
        ]);
        $searchModel->updateSession();
        $params = Yii::$app->request->isPost ? Yii::$app->request->post() : (Yii::$app->request->get('page') || Yii::$app->request->get('sort') || Yii::$app->request->isAjax ? $session['monthly_charge_search_params'] : []);
        $dataProvider = $searchModel->search($params);
        return $this->render('calc-collection-data', compact("searchModel", "dataProvider"));
    }

    public function actionExportCollectionData()
    {
        $session = Yii::$app->session;
        $params = $session['monthly_charge_search_params'];
        $params['MonthlyChargeSearch[export_collection_data]'] = 1;
        $searchModel = new MonthlyChargeSearch();
        $dataProvider = $searchModel->search($params);
        return $this->renderPartial('export-lease-contract-calc-collection-data-summary', compact("searchModel", "dataProvider"));
    }

    public function actionRemoveStoredCollectionData($id)
    {
        Url::remember();
        $model = TargetTermMonthlyChargeStored::findOne($id);
        Yii::$app->db->createCommand()->delete('stored_monthly_charge', ['target_term_monthly_charge_stored_id' => $model->target_term_monthly_charge_stored_id])
            ->execute();
        $session = Yii::$app->session;
        $session['requery-stored-collection-data'] = [
            'target_term' => (new \DateTime($model->target_term))->format('Y年n月'),
            'client_corporation_id' => $model->client_corporation_id,
            'repayment_pattern_id' => $model->repayment_pattern_id,
        ];
        $model->delete();
        $this->redirect(['/aas/stored-collection-data']);
    }

    public function actionStoreCollectionData()
    {
        $session = Yii::$app->session;
        $params = $session['monthly_charge_search_params'];
        TargetTermMonthlyChargeStored::register($params);
        return $this->redirect(['/aas/stored-collection-data']);
    }

    public function actionStoredCollectionData()
    {
        $session = Yii::$app->session;
        $params = $session['monthly_charge_search_params'];
        if (isset($params['MonthlyChargeSearch2'])) {
            $searchModel = new TargetTermMonthlyChargeStoredSearch([
                'target_term' => $params['MonthlyChargeSearch2']['target_term'],
                'client_corporation_id' => $params['MonthlyChargeSearch2']['client_corporation_id'],
                'repayment_pattern_id' => $params['MonthlyChargeSearch2']['repayment_pattern_id'],
            ]);
        } else {
            $searchModel = new TargetTermMonthlyChargeStoredSearch([
                'target_term' => (new \DateTime())->format('Y年n月'),
            ]);
        }
        $previous = Url::previous();
        if (Yii::$app->request->isPost) {
            $searchParams = Yii::$app->request->post();
            $session['stored-collection-data-search-params'] = $searchParams;
        }
        else if (strpos($previous, '/aas/close-stored-collection-data') === 0) {
            $searchParams = $session['stored-collection-data-search-params'] ?? [];
        }
        else if (strpos($previous, '/aas/unclose-stored-collection-data') === 0) {
            $searchParams = $session['stored-collection-data-search-params'] ?? [];
        }
        else if (strpos($previous, '/aas/remove-stored-collection-data') === 0) {
            $searchParams = $session['stored-collection-data-search-params'] ?? [];
        }
        else {
            $searchParams = [];
        }
        Url::remember();
        $dataProvider = $searchModel->search($searchParams);
        return $this->render('stored-collection-data', compact("searchModel", "dataProvider"));
    }

    public function actionCloseStoredCollectionData($id)
    {
        Url::remember();
        $model = TargetTermMonthlyChargeStored::findOne($id);
        $model->closeTerm();
        return $this->redirect(['/aas/stored-collection-data']);
    }

    public function actionUncloseStoredCollectionData($id)
    {
        Url::remember();
        $model = TargetTermMonthlyChargeStored::findOne($id);
        $model->uncloseTerm();
        return $this->redirect(['/aas/stored-collection-data']);
    }

    public function actionExportStoredCollectionData($id)
    {
        $model = TargetTermMonthlyChargeStored::findOne($id);
        return $this->renderPartial('export-lease-contract-stored-collection-data-summary', compact("model"));
    }

    public function actionRegisterStoredCollectionDataRecent($id)
    {
        set_time_limit(0);
        $model = TargetTermMonthlyChargeStored::findOne($id);
        $searchModel = new RepaymentSearch([
            'model' => $model,
        ]);
        $session = Yii::$app->session;
        if (!Yii::$app->request->isPost && !Yii::$app->request->isAjax) {
            $session['filter-name'] = null;
            $session['filter-code'] = null;
        }
        if (Yii::$app->request->isPost) {
            if (Yii::$app->request->post('RepaymentSearch', false)) {
                return $this->render('register-stored-collection-data', compact("model", "searchModel"));
            }
            else {
                $model->load(Yii::$app->request->post());
                $saved = 0;
                $posted = Yii::$app->request->post('Repayment', []);
                $repayments = [];
                $loaded = true;
                if (count($posted)) {
                    foreach ($posted as $data) {
                        $repayment = new Repayment([
                            'registered' => date('Y-m-d H:i:s'),
                        ]);
                        $loaded = $loaded && $repayment->load($data, '');
                        if ($model->transfer_date) {
                            $repayment->processed = $model->transfer_date;
                        }
                        $repayments[] = $repayment;
                    }
                    if ($loaded && Model::validateMultiple($repayments)) {
                        foreach ($repayments as $repayment) {
                            $monthlyCharge = MonthlyCharge::findOne($repayment->monthly_charge_id);
                            if ($monthlyCharge->getRepayments()->count() == 0 && $monthlyCharge->getDebts()->count() == 0 && $repayment->collected * 1) {
                                $repayment->save();
                                $saved++;
                            }
                        }
                        if ($saved) {
                            Yii::$app->session->setFlash('register-stored-collection-data', "{$saved}件の実績を登録しました。");
                        }
                    } else {
                        $errors = [];
                        foreach ($repayments as $repayment) {
                            if ($repayment->hasErrors()) {
                                $errors[] = $repayment->errors;
                            }
                        }
                    }
                }
                if ($cancelled = Yii::$app->request->post('CancelRepayment')) {
                    foreach ($cancelled as $repayment_id => $params) {
                        if ($params['cancelled'] == 1) {
                            $repayment = Repayment::findOne($repayment_id);
                            if ($repayment) {
                                $repayment->delete();
                            }
                        }
                    }
                }
            }
        }
        return $this->render('register-stored-collection-data', compact("model", "searchModel"));
    }

    public function actionRegisterStoredCollectionData($id)
    {
        set_time_limit(0);
        $model = TargetTermMonthlyChargeStored::findOne($id);
        $searchModel = new RepaymentSearch([
            'model' => $model,
        ]);
        $session = Yii::$app->session;
        if (!Yii::$app->request->isPost && !Yii::$app->request->isAjax) {
            $session['filter-name'] = null;
            $session['filter-code'] = null;
            $session['filter-repayment_type_id'] = in_array($model->repayment_pattern_id, [1,2,3]) ? 1 : null;
            $session['filter-contract_code'] = null;
        }
        if (Yii::$app->request->isPost) {
            if (Yii::$app->request->post('RepaymentSearch', false)) {
                return $this->render('register-stored-collection-data-alternative', compact("model", "searchModel"));
            }
            else {
                $model->load(Yii::$app->request->post());
                $saved = 0;
                $posted = Yii::$app->request->post('Repayment', []);
                $repayments = [];
                $loaded = true;
                if (count($posted)) {
                    foreach ($posted as $data) {
                        $repayment = new Repayment([
                            'registered' => date('Y-m-d H:i:s'),
                        ]);
                        $loaded = $loaded && $repayment->load($data, '');
                        if ($model->transfer_date) {
                            $repayment->processed = $model->transfer_date;
                        }
                        $repayments[] = $repayment;
                    }
                    if ($loaded && Model::validateMultiple($repayments)) {
                        foreach ($repayments as $repayment) {
                            $monthlyCharge = MonthlyCharge::findOne($repayment->monthly_charge_id);
                            if ($monthlyCharge->getRepayments()->count() == 0 && $monthlyCharge->getDebts()->count() == 0 && $repayment->collected * 1) {
                                $repayment->save();
                                $saved++;
                            }
                        }
                        if ($saved) {
                            Yii::$app->session->setFlash('register-stored-collection-data', "{$saved}件の実績を登録しました。");
                        }
                    } else {
                        $errors = [];
                        foreach ($repayments as $repayment) {
                            if ($repayment->hasErrors()) {
                                $errors[] = $repayment->errors;
                            }
                        }
                    }
                }
                if ($cancelled = Yii::$app->request->post('CancelRepayment')) {
                    foreach ($cancelled as $repayment_id => $params) {
                        if ($params['cancelled'] == 1) {
                            $repayment = Repayment::findOne($repayment_id);
                            if ($repayment) {
                                $repayment->delete();
                            }
                        }
                    }
                }
            }
        }
        return $this->render('register-stored-collection-data-alternative', compact("model", "searchModel"));
    }

    public function actionRegisterStoredCollectionDataByRow($id, $index = null)
    {

        $model = TargetTermMonthlyChargeStored::findOne($id);
        $searchModel = new RepaymentSearch([
            'model' => $model,
        ]);
        $session = Yii::$app->session;
        if (!Yii::$app->request->isPost && !Yii::$app->request->isAjax) {
            $session['filter-name'] = null;
            $session['filter-code'] = null;
        }
        if (Yii::$app->request->isPost) {
            if (Yii::$app->request->post('RepaymentSearch', false)) {
                return $this->render('register-stored-collection-data', compact("model", "searchModel"));
            }
            else {
                $model->load(Yii::$app->request->post());
                $posted = Yii::$app->request->post('Repayment', []);
                $repayments = [];
                print_r ($model);print_r ($posted);
                $loaded = true;
                if (count($posted)) {
                    foreach ($posted as $data) {
                        $repayment = new Repayment([
                            'registered' => date('Y-m-d H:i:s'),
                        ]);
                        // $loaded = $loaded && $repayment->load($data, '');
                        $repayment->load($data, '');
                        // $repayment = $data;
                        // echo $data;
                        if ($model->transfer_date) {
                            $repayment->processed = $model->transfer_date;
                        }
                        // $repayments[] = $repayment;
                        $monthlyCharge = MonthlyCharge::findOne($repayment->monthly_charge_id);
                        if ($monthlyCharge->getRepayments()->count() == 0 && $monthlyCharge->getDebts()->count() == 0 && $repayment->collected * 1) {
                            $repayment->save();
                            //return $this->renderPartial('register-stored-collection-data-by-row', compact("model", "searchModel", "monthlyCharge", "index"));
                            return $this->asJson(['success' => true]);
                        }
                    }
                    if ($loaded && Model::validateMultiple($repayments)) {
                        // foreach ($repayments as $repayment) {
                        //     $monthlyCharge = MonthlyCharge::findOne($repayment->monthly_charge_id);
                        //     if ($monthlyCharge->getRepayments()->count() == 0 && $monthlyCharge->getDebts()->count() == 0 && $repayment->collected * 1) {
                        //         $repayment->save();
                        //         //return $this->renderPartial('register-stored-collection-data-by-row', compact("model", "searchModel", "monthlyCharge", "index"));
                        //         return $this->asJson(['success' => true]);
                        //     }
                        // }
                    } else {
                        $errors = [];
                        foreach ($repayments as $repayment) {
                            if ($repayment->hasErrors()) {
                                $errors[] = $repayment->errors;
                            }
                        }
                        return $this->asJson($errors);
                    }
                }
            }
        }
    }

    public function actionRegisterRepaymentByCustomerRecent($id, $cid)
    {
        $model = TargetTermMonthlyChargeStored::findOne($id);
        $formModel = new RepaymentByCustomerForm([
            'repayment_type_id' => 2, //振込入金
        ]);
        $customer = Customer::findOne($cid);
        $contract_detail_ids = $model->getMonthlyCharges()->alias('mc')
            ->select(['cd.contract_detail_id'])
            ->innerJoin('contract_detail cd', 'mc.contract_detail_id=cd.contract_detail_id')
            ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
            ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
            ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
            ->andWhere(['c.customer_id' => $cid])
            ->orderBy([
                'cc.client_corporation_id' => SORT_ASC,
                'c.customer_code' =>SORT_ASC,
                'lc.disp_order' => SORT_ASC,
                'cd.contract_type' => SORT_ASC,
                'cd.term_start_at' =>SORT_ASC,
                'mc.monthly_charge_id' => SORT_ASC])
            ->column();
        $query = CollectionCell::find()->alias('coc')
            ->innerJoin('term tm', 'coc.term_id=tm.term_id')
            ->innerJoin('contract_detail cd', 'coc.contract_detail_id=cd.contract_detail_id')
            ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
            ->where(['and',
                ['coc.contract_detail_id' => $contract_detail_ids],
                ['<=', 'tm.term', $model->target_term],
                'coc.monthly_charge_amount_with_tax - coc.repayment_amount_with_tax > 0'
            ]);
        if ($formModel->load(Yii::$app->request->post()) && $formModel->validate()) {
            $formModel->registerRepayment();
            if ($formModel->pooled_repayment > 0) {
                $customer->pooled_repayment = $formModel->pooled_repayment;
                $customer->save();
            }
            return $this->redirect(['/aas/register-stored-collection-data', 'id' => $id]);
        }

        return $this->render('register-repayment-by-customer', compact("model", "formModel", "customer", "query"));
    }

    public function actionRegisterRepaymentByCustomer($id, $cid)
    {
        $model = TargetTermMonthlyChargeStored::findOne($id);
        
        $target_term = new \DateTime($model->target_term);
        $target_term = $target_term->modify('+1 month')->format('Y-m-d');
        $formModel = new RepaymentByCustomerForm([
            'repayment_type_id' => 2, //振込入金
        ]);
        $customer = Customer::findOne($cid);
        $contract_detail_ids = MonthlyCharge::find()->alias('mc')
            ->select(['cd.contract_detail_id'])
            ->innerJoin('contract_detail cd', 'mc.contract_detail_id=cd.contract_detail_id')
            ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
            ->innerJoin('customer c', 'lc.customer_id=c.customer_id')
            ->innerJoin('client_contract cc', 'c.client_contract_id=cc.client_contract_id')
            ->andWhere(['c.customer_id' => $cid])
            ->orderBy([
                'cc.client_corporation_id' => SORT_ASC,
                'c.customer_code' =>SORT_ASC,
                'lc.disp_order' => SORT_ASC,
                'cd.contract_type' => SORT_ASC,
                'cd.term_start_at' =>SORT_ASC,
                'mc.monthly_charge_id' => SORT_ASC])
            ->column();
        $query = CollectionCell::find()->alias('coc')
            ->innerJoin('term tm', 'coc.term_id=tm.term_id')
            ->innerJoin('contract_detail cd', 'coc.contract_detail_id=cd.contract_detail_id')
            ->innerJoin('lease_contract lc', 'cd.lease_contract_id=lc.lease_contract_id')
            ->where(['and',
                ['coc.contract_detail_id' => $contract_detail_ids],
                ['<=', 'tm.term', $model->target_term],
                'coc.monthly_charge_amount_with_tax - coc.repayment_amount_with_tax > 0'
            ]);
        if ($formModel->load(Yii::$app->request->post()) && $formModel->validate()) {
            $formModel->registerRepayment();
            if ($formModel->pooled_repayment > 0) {
                $customer->pooled_repayment = $formModel->pooled_repayment;
                $customer->save();
            }
            return $this->redirect(['/aas/register-stored-collection-data', 'id' => $id]);
        }

        return $this->render('register-repayment-by-customer', compact("model", "formModel", "customer", "query"));
    }

    public function actionRedirectToRegisterRepaymentByCustomer($id)
    {
        $customer = Customer::findOne($id);
        $sql = <<<SQL
    r.repayment_amount < CASE cd.fraction_processing_pattern 
    WHEN 'ceil' THEN CEIL(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    WHEN 'floor' THEN FLOOR(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    WHEN 'roundup' THEN ROUND(IFNULL(mc.temporary_charge_amount, mc.charge_amount) * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE mc.term >= application_from AND mc.term <= IFNULL(application_to, '2099-12-31')) END))
    END
SQL;
        $model = TargetTermMonthlyChargeStored::find()->alias('t')
            ->innerJoin('stored_monthly_charge smc', 't.target_term_monthly_charge_stored_id=smc.target_term_monthly_charge_stored_id')
            ->innerJoin('monthly_charge mc', 'smc.monthly_charge_id=mc.monthly_charge_id')
            ->leftJoin('repayment r', 'mc.monthly_charge_id=r.monthly_charge_id')
            ->innerJoin('contract_detail cd', 'mc.contract_detail_id=cd.contract_detail_id')
            ->innerJoin('tax_application ta', 'cd.tax_application_id=ta.tax_application_id')
            ->innerJoin('lease_contract lc', 'lc.lease_contract_id=cd.lease_contract_id')
            ->where(['and', ['or', ['r.repayment_id' => null], $sql], ['lc.customer_id' => $id]])
            ->orderBy(['t.target_term_monthly_charge_stored_id' => SORT_DESC])
            ->limit(1)
            ->one();
        if ($model) {
            return $this->redirect(['/aas/register-repayment-by-customer', 'id' => $model->target_term_monthly_charge_stored_id, 'cid' => $id]);
        }
        return $this->render('redirect-to-register-repayment-by-customer', compact("customer"));
    }

    public function actionRegisterRepayments()
    {
        if (!Yii::$app->request->isPost || !Yii::$app->request->isAjax) {
            $session['filter-name'] = null;
            $session['filter-code'] = null;
        }
        $session = Yii::$app->session;
        $params = $session['monthly_charge_search_params'];
        $params['MonthlyChargeSearch[export_collection_data]'] = 1;
        $searchModel = new MonthlyChargeSearch([
            'register_repayments' => 1
        ]);
        $dataProvider = $searchModel->search($params);
        $targetTerm = (new \DateTime(preg_replace('/(\d+)年(\d+)月/', '$1-$2-1', $searchModel->target_term)))->format('Ym');
        $dataProvider->query
            ->leftJoin('monthly_charge mc', 'cd.contract_detail_id=mc.contract_detail_id AND DATE_FORMAT(mc.term, "%Y%m") = :term')
            ->leftJoin('repayment r', 'cd.contract_detail_id=r.contract_detail_id AND DATE_FORMAT(CASE rp.target_month WHEN "next" THEN mc.term + INTERVAL 1 MONTH ELSE mc.term END, "%Y%m") = DATE_FORMAT(r.processed, "%Y%m")')
            ->leftJoin('debt d', 'cd.contract_detail_id=d.contract_detail_id AND DATE_FORMAT(CASE rp.target_month WHEN "next" THEN mc.term + INTERVAL 1 MONTH ELSE mc.term END, "%Y%m") = DATE_FORMAT(d.term, "%Y%m")')
            ->params([':term' => $targetTerm])
            ->andWhere(['and', ['r.repayment_id' => null], ['d.debt_id' => null]]);
        if (!empty($session['filter-name'])) {
            $dataProvider->query->andWhere(['like', 'c.name', $session['filter-name']]);
        }
        if (!empty($session['filter-code'])) {
            $dataProvider->query->andWhere(['like', 'c.customer_code', $session['filter-code']]);
        }
        if (Yii::$app->request->isPost) {
            $posted = Yii::$app->request->post('Repayment', []);
            $repayments = [];
            $loaded = true;
            if (count($posted)) {
                foreach ($posted as $data) {
                    $repayment = new Repayment([
                        'registered' => date('Y-m-d H:i:s'),
                    ]);
                    $loaded = $loaded && $repayment->load($data, '');
                    $repayments[] = $repayment;
                }
                if ($loaded && Model::validateMultiple($repayments)) {
                    foreach ($repayments as $repayment) {
                        if ($repayment->collected) {
                            $repayment->save();
                        }
                    }
                } else {
                    $errors = [];
                    foreach ($repayments as $repayment) {
                        if ($repayment->hasErrors()) {
                            $errors[] = $repayment->errors;
                        }
                    }
                    return VarDumper::dumpAsString(compact("loaded", "errors"), 10, 1);
                }
            }
        }
        return $this->render('register-repayments', compact("searchModel", "dataProvider"));
    }

    public function actionLeaseContractCollection()
    {

    }

    public function actionContractDetail($lcid = null, $index)
    {
        $model = new ContractDetail([
            'lease_contract_id' => $lcid,
            'fraction_processing_pattern' => 'roundup',
            'advance_repayment_count' => 0,
        ]);
        $form = new ActiveForm();
        return $this->renderAjax('contract-detail2', compact("model", "form", "index"));
    }

    public function actionCalcTaxIncluded($id, $amount, $method)
    {
        $methods = [
            'floor' => 'FLOOR',
            'ceil' => 'CEIL',
            'roundup' => 'ROUND'
        ];
        $sql = "SELECT {$methods[$method]}(:amount * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE NOW() >= application_from AND NOW() <= IFNULL(application_to, '2099-12-31')) END)) FROM tax_application ta WHERE ta.tax_application_id=:id";
        $value = Yii::$app->db->createCommand($sql)->bindValues([
            ':amount' => (int)$amount,
            ':id' => (int)$id,
        ])->queryScalar();
        return $this->asJson(compact("value"));
    }

    public function actionCalcAmountFromTaxIncluded($id, $amountWithTax, $method)
    {
        $methods = [
            'floor' => 'CEIL',
            'ceil' => 'FLOOR',
            'roundup' => 'ROUND'
        ];
        $methods2 = [
            'floor' => 'FLOOR',
            'ceil' => 'CEIL',
            'roundup' => 'ROUND'
        ];
        $sql = "SELECT {$methods[$method]}(:amount / (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE NOW() >= application_from AND NOW() <= IFNULL(application_to, '2099-12-31')) END)) FROM tax_application ta WHERE ta.tax_application_id=:id";
        $value = Yii::$app->db->createCommand($sql)->bindValues([
            ':amount' => (int)$amountWithTax,
            ':id' => (int)$id,
        ])->queryScalar();
        $sql2 = "SELECT {$methods2[$method]}(:value * (1 + CASE ta.fixed WHEN 1 THEN ta.tax_rate ELSE (SELECT rate FROM consumption_tax_rate WHERE NOW() >= application_from AND NOW() <= IFNULL(application_to, '2099-12-31')) END)) FROM tax_application ta WHERE ta.tax_application_id=:id";
        $valueWithTax = Yii::$app->db->createCommand($sql2)->bindValues([
            ':value' => (int)$value,
            ':id' => (int)$id,
        ])->queryScalar();
        return $this->asJson(compact("value","valueWithTax"));
    }

    public function actionContractChart($id)
    {
        $model = LeaseContract::findOne($id);
        return $this->render('contract-chart', compact("model"));
    }

    public function actionRepaymentTypes()
    {
        $searchModel = new RepaymentTypeSearch();
        if (Yii::$app->request->isPost) {
            $searchModel->load(Yii::$app->request->post());
        }
        $dataProvider = $searchModel->search(Yii::$app->request->post());
        return $this->render('repayment-types', compact("searchModel", "dataProvider"));
    }

    public function actionRepaymentType($id = null)
    {
        if (isset($id)) {
            $model = RepaymentType::findOne($id);
        } else {
            $model = new RepaymentType([
                'disp_order' => RepaymentType::find()->max('disp_order') + 1
            ]);
        }
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->save();
            return $this->redirect(['/aas/repayment-types']);
        }
        return $this->render('repayment-type', compact("model"));
    }

    public function actionRemoveRepaymentType($id)
    {
        $model = RepaymentType::findOne($id);
        $applied = $model->getRepayments()->count();
        if ($model && $applied == 0) {
            $model->removed = date('Y-m-d H:i:s');
            $model->save();
        }
        return $this->redirect(['/aas/repayment-types']);
    }

    public function actionRankupRepaymentType($id)
    {
        $model = RepaymentType::findOne($id);
        $target = RepaymentType::findOne(['disp_order' => $model->disp_order - 1]);
        $model->disp_order -= 1;
        $target->disp_order += 1;
        $model->save();
        $target->save();
        return $this->redirect(['/aas/repayment-types']);
    }

    public function actionRankdownRepaymentType($id)
    {
        $model = RepaymentType::findOne($id);
        $target = RepaymentType::findOne(['disp_order' => $model->disp_order + 1]);
        $model->disp_order += 1;
        $target->disp_order -= 1;
        $model->save();
        $target->save();
        return $this->redirect(['/aas/repayment-types']);
    }

    public function actionRankupLeaseContractStatusType($id)
    {
        $model = LeaseContractStatusType::findOne($id);
        $target = LeaseContractStatusType::findOne(['disp_order' => $model->disp_order - 1]);
        $model->disp_order -= 1;
        $target->disp_order += 1;
        $model->save();
        $target->save();
        return $this->redirect(['/aas/lease-contract-statuses']);
    }

    public function actionRankdownLeaseContractStatusType($id)
    {
        $model = LeaseContractStatusType::findOne($id);
        $target = LeaseContractStatusType::findOne(['disp_order' => $model->disp_order + 1]);
        $model->disp_order += 1;
        $target->disp_order -= 1;
        $model->save();
        $target->save();
        return $this->redirect(['/aas/lease-contract-statuses']);
    }

    public function actionMonthsCount($from, $to)
    {
        $startMatched = preg_match('/(\d+)-(\d+)-(\d+)/', $from) ? new \DateTime($from) : false;
        $endMatched = preg_match('/(\d+)-(\d+)-(\d+)/', $to) ? new \DateTime($to) : false;
        if (!!$startMatched && !!$endMatched) {
            $startMonthCount = (int)($startMatched->format('Y')) * 12 + (int)($startMatched->format('n')) * 1 - 1;
            $endMonthCount = (int)($endMatched->format('Y')) * 12 + (int)($endMatched->format('n')) * 1 - 1;
            $fd = (int)($startMatched->format('d'));
            if ($fd == 1) {
                $passed = $endMatched->format('d') == $endMatched->format('t') ? 1 : 0;
            } else if ($fd > 1 && $fd < 29) {
                $passed = $fd - 1 <= (int)($endMatched->format('d')) ? 1 : 0;
            } else {

            }
        }
    }
}