<?php

namespace Backend\Modules\MiniBlog\Actions;

use Backend\Core\Engine\Base\ActionAdd as BackendBaseActionAdd;
use Backend\Core\Engine\Authentication as BackendAuthentication;
use Backend\Core\Engine\Form as BackendForm;
use Backend\Core\Engine\Language as BL;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\Meta as BackendMeta;
use Backend\Modules\Search\Engine\Model as BackendSearchModel;
use Backend\Modules\Tags\Engine\Model as BackendTagsModel;
use Backend\Modules\MiniBlog\Engine\Model as BackendMiniBlogModel;

/**
 * This action will add a post to the blog module.
 *
 * @author Stef Bastiaansen <stef.bastiaansen@netlash.com>
 * @author Lander Vanderstraeten <lander.vanderstraeten@wijs.be>
 */
class Add extends BackendBaseActionAdd
{
    /**
     * Execute the action.
     * AANPASSING !!! : void aan execute toegevoegd
     */
    public function execute(): void
    {
        parent::execute();

        $this->loadForm();
        $this->validateForm();

        $this->parse();
        $this->display();
    }

    /**
     * Load the form.
     */
    private function loadForm()
    {
        $this->frm = new BackendForm('add');
        $this->frm->addText('title', null, 255, 'inputText title', 'inputTextError title');
        $this->frm->addEditor('introduction');
        $this->frm->addEditor('text');
        $this->frm->addCheckbox('publish', true);
        $this->frm->addText('tags', null, null, 'inputText tagBox', 'inputTextError tagBox');

        $this->meta = new BackendMeta($this->frm, null, 'title', true);
    }

    /**
     * Validate the form.
     */
    private function validateForm()
    {
        if ($this->frm->isSubmitted()) {
            $this->frm->cleanupFields();

            // validation
            $this->frm->getField('title')->isFilled(BL::err('TitleIsRequired'));
            $this->frm->getField('introduction')->isFilled(BL::err('FieldIsRequired'));
            $this->frm->getField('text')->isFilled(BL::err('FieldIsRequired'));
            $this->meta->validate();

            // no errors?
            if ($this->frm->isCorrect()) {
                $item['meta_id'] = $this->meta->save();
                $item['language'] = BL::getWorkingLanguage();
                $item['title'] = $this->frm->getField('title')->getValue();
                $item['introduction'] = $this->frm->getField('introduction')->getValue();
                $item['text'] = $this->frm->getField('text')->getValue();
                $item['publish'] = $this->frm->getField('publish')->getChecked() ? 'Y' : 'N';
                $item['user_id'] = BackendAuthentication::getUser()->getUserId();
                $item['created'] = date('Y-m-d H:i:s');
                $item['edited'] = date('Y-m-d H:i:s');

                $item['id'] = BackendMiniBlogModel::insert($item);

                // save the tags
                BackendTagsModel::saveTags($item['id'], $this->frm->getField('tags')->getValue(), $this->URL->getModule());

                // add searchindex
                BackendSearchModel::saveIndex('blog', $item['id'], array('title' => $item['title'], 'introduction' => $item['introduction'], 'text' => $item['text']));

                // trigger an event
                BackendModel::triggerEvent('mini_blog', 'after_add', $item);

                // everything is saved, so redirect to the overview
                $this->redirect(BackendModel::createURLForAction('Index').'&report=added&var='.urlencode($item['title']).'&highlight=row-'.$item['id']);
            }
        }
    }
}
