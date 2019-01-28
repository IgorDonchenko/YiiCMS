<?php
/**
 * User: Vladimir Baranov <phpnt@yandex.ru>
 * Git: <https://github.com/phpnt>
 * VK: <https://vk.com/phpnt>
 * Date: 19.01.2019
 * Time: 10:18
 */

use yii\bootstrap\Html;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;
use phpnt\summernote\SummernoteWidget;

/* @var $this yii\web\View */
/* @var $document_id int */
/* @var $comment_id int */
/* @var $access_answers boolean */
/* @var $modelCommentForm \common\models\forms\CommentForm */
?>
<div id="block-item-comment">
    <div class="row">
        <?php $form = ActiveForm::begin([
            'id' => 'form-comment',
            'action' => $modelCommentForm->isNewRecord ? Url::to(['/comment/create-comment', 'document_id' => $document_id, 'comment_id' => $comment_id, 'access_answers' => $access_answers]) : Url::to(['/comment/update-comment', 'document_id' => $document_id, 'comment_id' => $comment_id, 'access_answers' => $access_answers]),
            'options' => ['data-pjax' => true]
        ]); ?>

        <div class="col-md-12 text-left">
            <?= $form->field($modelCommentForm, 'text')->widget(SummernoteWidget::class,[
                'options' => [
                    'id' => 'summernote' . $modelCommentForm->id,
                    'class' => 'hidden',
                ],
                'i18n' => true,             // переводить на другие языки
                'codemirror' => true,       // использовать CodeMirror (оформленный редактор кода)
                'emoji' => true,             // включить эмоджи
                'widgetOptions' => [
                    /* Настройка панели */
                    'placeholder' => Yii::t('app', 'Ваш комментарий.'),
                    'height' => 200,
                    'tabsize' => 2,
                    'minHeight' => 200,
                    'maxHeight' => 200,
                    'focus' => false,
                    'disableResizeImage' => true,
                    'disableResize' => true,
                    'popover' => false,
                    /* Панель управления */
                    'toolbar' => [
                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        /*['font', ['strikethrough', 'superscript', 'subscript']],
                        ['fontsize', ['fontsize']],
                        ['color', ['color']],
                        ['para', ['paragraph']],
                        ['height', ['height']],
                        ['misc', ['codeview']],*/
                    ],
                    'callbacks' => [
                        'onFocus' => new \yii\web\JsExpression(
                            'function (data) {
                                console.log(data);
                            }'
                        )
                    ],
                ],
            ])->label(false); ?>
        </div>

        <div class="col-md-12 form-group">
            <?= Html::submitButton(Yii::t('app', 'Отправить'), ['class' => 'btn btn-primary']) ?>
            <?= Html::button(Yii::t('app', 'Отмена'),
                [
                    'class' => 'btn btn-danger',
                    'onclick' => '
                        $.pjax({
                            type: "GET",
                            url: "' . Url::to(['/comment/refresh-comment', 'document_id' => $document_id]) . '",
                            container: "#block-comment-' . $document_id . '",
                            push: false,
                            timeout: 10000,
                            scrollTo: false
                        })'
                ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
        <?php
        $url_refresh = Url::to(['/comment/refresh-comment', 'document_id' => $document_id, 'access_answers' => $access_answers]);
        $block_refresh = '#block-comment-' . $document_id;
        $js = <<< JS
        $('#form-comment').on('beforeSubmit', function () { 
            var form = $(this);
                $.pjax({
                    type: form.attr('method'),
                    url: form.attr('action'),
                    data: new FormData($('#form-comment')[0]),
                    container: "#block-item-comment",
                    push: false,
                    scrollTo: false,
                    cache: false,
                    contentType: false,
                    timeout: 10000,
                    processData: false
                })
                .done(function(data) {
                    try {
                        var result = jQuery.parseJSON(data);
                    } catch (e) {
                        return false;
                    }
                    if(result.success) {
                        console.log('ok');
                        // data is saved
                        $.pjax({
                            type: "GET", 
                            url: "$url_refresh",
                            container: "$block_refresh",
                            push: false,
                            timeout: 20000,
                            scrollTo: false
                        });
                    } else if (result.validation) {
                        // server validation failed
                        console.log('validation failed');
                        form.yiiActiveForm('updateMessages', data.validation, true); // renders validation messages at appropriate places
                    } else {
                        // incorrect server response
                        console.log('incorrect server response');
                    }
                })
                .fail(function () {
                    // request failed
                    console.log('request failed');
                })
            return false; // prevent default form submission
        });
JS;
        $this->registerJs($js); ?>
    </div>
</div>
