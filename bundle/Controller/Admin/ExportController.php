<?php

namespace Netgen\Bundle\InformationCollectionBundle\Controller\Admin;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\ContentService;
use Netgen\InformationCollection\API\Service\Exporter;
use Netgen\InformationCollection\API\Value\Export\ExportCriteria;
use Netgen\Bundle\InformationCollectionBundle\Form\Type\ExportType;
use Symfony\Component\HttpFoundation\Request;
use League\Csv\Writer;
use SplTempFileObject;
use Symfony\Component\HttpFoundation\Response;

final class ExportController extends Controller
{
    /**
     * @var \eZ\Publish\API\Repository\ContentService
     */
    protected $contentService;

    /**
     * @var \Netgen\InformationCollection\API\Service\Exporter
     */
    protected $exporter;

    public function __construct(ContentService $contentService, Exporter $exporter)
    {
        $this->contentService = $contentService;
        $this->exporter = $exporter;
    }

    /**
     * Handles export
     *
     * @param int $contentId
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function exportAction($contentId, Request $request)
    {
//        $this->denyAccessUnlessGranted('ez:infocollector:read');

        $content = $this->contentService->loadContent($contentId);

        $form = $this->createForm(ExportType::class);
        $form->handleRequest($request);

        if ($form->get('cancel')->isClicked()) {
            return $this->redirect($this->generateUrl('netgen_information_collection.route.admin.overview'));
        }

        if ($form->isValid() && $form->get('export')->isClicked()) {

            $exportCriteria = new ExportCriteria(
                [
                    'content' => $content,
                    'from' => $form->getData('dateFrom'),
                    'to' => $form->getData('dateTo'),
                ]
            );

            $export = $this->exporter->export($exportCriteria);

            $writer = Writer::createFromFileObject(new SplTempFileObject());
            $writer->setDelimiter(",");
            $writer->setNewline("\r\n"); //use windows line endings for compatibility with some csv libraries
            $writer->setOutputBOM(Writer::BOM_UTF8); //adding the BOM sequence on output
            $writer->insertOne($export->header);
            $writer->insertAll($export->contents);

            $writer->output('export.csv');
            return new Response('');
        }

        return $this->render("@NetgenInformationCollection/admin/export_menu.html.twig",
            [
                'content' => $content,
                'form' => $form->createView(),
            ]
        );
    }
}
