<?php

namespace GuaranteedOpinion\Controller;

use Thelia\Controller\Front\BaseFrontController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/GuaranteedOpinion', name: 'GuaranteedOpinion')]
class FrontController extends BaseFrontController
{

  #[Route('/GetGuaranteedOpinion/{page}', name: 'GetGuaranteedOpinion', methods: ['GET'])]
  public function getGuaranteedOpinionAction(int $page)
  {
    return $this->render('product/next-five-reviews', ['page' => $page]);
  }
}
