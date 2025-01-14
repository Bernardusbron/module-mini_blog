<?php

namespace Backend\Modules\MiniBlog\Actions;

use Backend\Core\Engine\Base\ActionIndex as BackendBaseActionIndex;
use Backend\Core\Engine\Language as BL;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\DataGridDB as BackendDataGridDB;
use Backend\Modules\MiniBlog\Engine\Model as BackendMiniBlogModel;
use Backend\Core\Engine\DataGridFunctions as BackendDataGridFunctions;

/**
 * This is the index-action (default), it will display the overview of miniblog posts.
 *
 * @author Dave Lens <dave.lens@netlash.com>
 * @author Davy Hellemans <davy.hellemans@netlash.com>
 * @author Matthias Mullie <matthias.mullie@netlash.com>
 * @author Stef Bastiaansen <stef.bastiaansen@netlash.com>
 * @author Lander Vanderstraeten <lander.vanderstraeten@wijs.be>
 */
class Index extends BackendBaseActionIndex
{
    /**
     * datagrid with published items.
     *
     * @var SpoonDataGrid
     */
    private $dgPublished;

    /**
     * datagrid with unpublished items.
     *
     * @var SpoonDataGrid
     */
    private $dgNotYetPublished;

    /**
     * Execute the action.
     */
    public function execute(): void
    {
        parent::execute();

        $this->dgPublished = $this->loadDataGrid('Y');
        $this->dgNotYetPublished = $this->loadDataGrid('N');

        $this->parse();
        $this->display();
    }

    /**
     * Loads the datagrid with the post.
     *
     * @param string $published 'Y' or 'N'.
     */
    private function loadDataGrid($published)
    {
        // create datagrid
        $dg = new BackendDataGridDB(BackendMiniBlogModel::QRY_DATAGRID_BROWSE, array($published, BL::getWorkingLanguage()));

        // set headers
        $dg->setHeaderLabels(array('user_id' => ucfirst(BL::lbl('Author'))));

        // sorting columns
        $dg->setSortingColumns(array('created', 'title', 'user_id'), 'created');
        $dg->setSortParameter('desc');

        // set colum URLs
        $dg->setColumnURL('title', BackendModel::createURLForAction('Edit').'&amp;id=[id]');

        // set column functions
        $dg->setColumnFunction(array(new BackendDataGridFunctions(), 'getLongDate'), array('[created]'), 'created', true);
        $dg->setColumnFunction(array(new BackendDataGridFunctions(), 'getUser'), array('[user_id]'), 'user_id', true);

        // add edit column
        $dg->addColumn('edit', null, BL::lbl('Edit'), BackendModel::createURLForAction('Edit').'&amp;id=[id]', BL::lbl('Edit'));

        // add delete column
        $dg->addColumn('delete', null, BL::lbl('Delete'), BackendModel::createURLForAction('Delete').'&amp;id=[id]', BL::lbl('Delete'));

        // our JS needs to know an id, so we can highlight it
        $dg->setRowAttributes(array('id' => 'row-[id]'));

        return $dg;
    }

    /**
     * Parse all datagrids.
     */
    protected function parse(): void
    {
        // parse the datagrid for all blogposts
        if ($this->dgPublished->getNumResults() != 0) {
            $this->tpl->assign('dgPublished', $this->dgPublished->getContent());
        }
        if ($this->dgNotYetPublished->getNumResults() != 0) {
            $this->tpl->assign('dgNotYetPublished', $this->dgNotYetPublished->getContent());
        }

        if ($this->dgNotYetPublished->getNumResults() == 0 && $this->dgPublished->getNumResults() == 0) {
            $this->tpl->assign('noItems', 1);
        }
    }
}
