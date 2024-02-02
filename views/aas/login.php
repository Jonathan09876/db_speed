<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$style=<<<EOS
.auth-one-bg {
    background-image:none !important;
}
EOS;
$this->registerCss($style);

$this->registerJsFile('/assets/libs/particles.js/particles.js');
$this->registerJsFile('/assets/js/pages/particles.app.js');
$this->registerJsFile('/assets/js/pages/password-addon.init.js');
$this->title = 'ログイン';
?>
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card mt-4">

                            <div class="card-body p-4">
                                <div class="text-center mt-2">
                                    <img src="/assets/images/svg/logo.svg" alt="" width="160" />
                                    <p class="text-muted">下記内容を入力の上、ログインボタンを押してください。</p>
                                </div>
                                <div class="p-2 mt-4">
                                    <?php $form = ActiveForm::begin([
                                        'id' => 'login-form',
                                    ]); ?>


                                    <div class="mb-3">
                                            <label for="username" class="form-label">ユーザー名</label>
                                            <?= Html::activeTextInput($model, 'username', ['class' => 'form-control', 'placeholder' => 'ユーザー名を入力してください。']) ?>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="password-input">パスワード</label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <?= Html::activePasswordInput($model, 'password', ['class' => 'form-control pe-5 password-input', 'placeholder' => 'パスワードを入力してください。', 'id' => 'password-input']) ?>
                                                <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                            </div>
                                        </div>

                                        <div class="form-check">
                                            <?= Html::activeCheckbox($model, 'rememberMe') ?>
                                        </div>

                                        <div class="mt-4">
                                            <button class="btn btn-success w-100" type="submit">ログイン</button>
                                        </div>

                                    <?php ActiveForm::end(); ?>
                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->

                    </div>
                </div>
                <!-- end row -->


