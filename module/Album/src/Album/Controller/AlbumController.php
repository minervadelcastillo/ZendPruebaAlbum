<?php

namespace Album\Controller;

use Album\Model\Album;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AlbumController extends AbstractActionController
{
    protected $albumTable;

    public function getAlbumTable()
    {
        if (!$this->albumTable) {
            $sm = $this->getServiceLocator();
            $this->albumTable = $sm->get('Album\Model\AlbumTable');
        }
        return $this->albumTable;
    }


    public function indexAction()
    {
        return new ViewModel(array(
            'albums' => $this->getAlbumTable()->fetchAll(),
        ));
    }

    public function addAction()
    {
        $request = $this->getRequest();
        $data = array(
            'title' => $request->getPost('title'),
            'artist' => $request->getPost('artist')
        );

        $album = new Album();

        if ($request->isPost()) {
            $album->exchangeArray($data);
            if ($this->getAlbumTable()->saveAlbum($album)) {
                return $this->redirect()->toRoute('album');
            }
        }

        return new viewModel();
    }

    public function editAction()
    {
        $request = $this->getRequest();
        $id = (int) $this->params()->fromRoute('id');
        $album = $this->getAlbumTable()->getAlbum($id);
        if ($request->isPost()) {
            $array = array(
                'id' => $id,
                'title' => $request->getPost('titulo'),
                'artist' => $request->getPost('artista')
            );
            $album->exchangeArray($array);
            $this->getAlbumTable()->saveAlbum($album);
            return $this->redirect()->toRoute('album');
        }
        return new ViewModel(array('id' => $id));
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('album');
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $del = $request->getPost('del', 'No');

            if ($del == 'Yes') {
                $id = (int) $request->getPost('id');
                $this->getAlbumTable()->deleteAlbum($id);
            }

            // Redirect to list of albums
            return $this->redirect()->toRoute('album');
        }

        return array(
            'id' => $id,
            'album' => $this->getAlbumTable()->getAlbum($id)
        );
    }

    /**
     * @return \Zend\Stdlib\ResponseInterface
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \PHPExcel_Exception
     */
    public function exportAction()
    {
        $titulos = array(
            array(
                'id',
                'titulo',
                'artista'
            )
        );

        $dataArray = array();
        $albums = $this->getAlbumTable()->fetchAll();
        foreach ($albums as $album) {
            $dataArray[] = [
                $album->id,
                $album->title,
                $album->artist
            ];
        }

        $data = array_merge($titulos, $dataArray);

        $doc = new \PHPExcel();

        $doc->setActiveSheetIndex(0);

        $doc->getActiveSheet()->fromArray($data);

        $filename = 'album.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename);
        header('Cache-Control: max-age=0'); //no cache

        $objWriter = \PHPExcel_IOFactory::createWriter($doc, 'Excel2007');

        $objWriter->save('php://output');

        //Comentario de prueba
    }
}
