<?php
/**
 * Created by PhpStorm.
 * User: Willem
 */
namespace org\ccextractor\submissionplatform\controllers;

use Slim\App;

class UploadController extends BaseController
{
    /**
     * UploadController constructor.
     */
    public function __construct()
    {
        parent::__construct("Upload","Upload samples to the repository.");
    }

    function register(App $app)
    {
        $self = $this;
        $app->group('/upload', function () use ($self) {
            // GET: show start of controller
            $this->get('[/]', function ($request, $response, $args) use ($self) {
                $self->setDefaultBaseValues($this);
                if($this->account->isLoggedIn()){
                    // TODO: add list of unprocessed ones
                    return $this->view->render($response,"upload/explain.html.twig",$this->templateValues->getValues());
                }
                return $this->view->render($response->withStatus(403),"login-required.html.twig",$this->templateValues->getValues());
            })->setName($self->getPageName());
            // GET: FTP upload details
            $this->get('/ftp', function ($request, $response, $args) use ($self) {
                $self->setDefaultBaseValues($this);
                if($this->account->isLoggedIn()){
                    $this->templateValues->add("host", $this->FTPConnector->getHost());
                    $this->templateValues->add("port", $this->FTPConnector->getPort());

                    $this->templateValues->add("username", "TODO");
                    $this->templateValues->add("password", "TODO");
                    // TODO: finish
                    return $this->view->render($response,"upload/explain-ftp.html.twig",$this->templateValues->getValues());
                }
                return $this->view->render($response->withStatus(403),"login-required.html.twig",$this->templateValues->getValues());
            })->setName($self->getPageName().'_ftp');
            // GET: HTTP upload
            $this->get('/new', function ($request, $response, $args) use ($self) {
                $self->setDefaultBaseValues($this);
                if($this->account->isLoggedIn()){
                    // TODO: finish
                }
                return $this->view->render($response->withStatus(403),"login-required.html.twig",$this->templateValues->getValues());
            })->setName($self->getPageName().'_new');
            // Logic for finalizing samples
            $this->group('/process', function () use ($self){
                $this->get('[/]', function ($request, $response, $args) use ($self) {
                    $self->setDefaultBaseValues($this);
                    if($this->account->isLoggedIn()){
                        // TODO: finish
                    }
                    return $this->view->render($response->withStatus(403),"login-required.html.twig",$this->templateValues->getValues());
                })->setName($self->getPageName().'_process');
                $this->get('/{id:[0-9]+}', function ($request, $response, $args) use ($self) {
                    $self->setDefaultBaseValues($this);
                    if($this->account->isLoggedIn()){
                        // TODO: finish
                    }
                    return $this->view->render($response->withStatus(403),"login-required.html.twig",$this->templateValues->getValues());
                })->setName($self->getPageName().'_process_id');
            });
        });
    }
}