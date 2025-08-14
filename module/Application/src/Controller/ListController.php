<?php
declare(strict_types=1);

namespace Application\Controller;

use Application\Domain\DomainExceptions\MongodbException;
use Application\Module;
use Application\Services\Instrument\InstrumentListService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

/**
 * @method \Laminas\Http\PhpEnvironment\Response getResponse()
 * @method \Laminas\Http\PhpEnvironment\Request getRequest()
 */
class ListController extends AbstractActionController
{
    /**
     * @var InstrumentListService
     */
    protected $instrumentListService;


    public function __construct(
        InstrumentListService $instrumentListService
    )
    {
        $this->instrumentListService = $instrumentListService;
    }


    public function listAction(): JsonModel
    {
        $startTime = microtime(true);
        $return = Module::newAppJsonModel()->setStatusError();
        $bid = $this->params()->fromRoute('bid');
        try {
            $instruments = $this->instrumentListService->expiredInstrumentsBefore(
                (int) $this->params()->fromQuery('limit', 10),
                0,
                new \DateTime('now'),
                $bid ? (float) $bid : null
            );
        }
        catch (MongodbException $exception) {
            $this->getResponse()->setStatusCode(500);
            return $return
                ->setMessage('error occurred')
                ->setThrowableFromSolvians($exception)
                ->setVariable('requestTime', (int) ((microtime(true) - $startTime) * 1000));
        }
        catch (\Throwable $exception) {
            $this->getResponse()->setStatusCode(503);
            return $return
                ->setMessage('server error occurred')
                ->setThrowableFromSolvians($exception)
                ->setVariable('requestTime', (int) ((microtime(true) - $startTime) * 1000));
        }
        $this->getResponse()->setStatusCode(200);
        $requestTime = (int) ((microtime(true) - $startTime) * 1000);
        return $return
            ->setStateStatusOk()
            ->setVariable('count', count($instruments))
            ->setData($instruments)
            ->setVariable('requestTime', $requestTime);
    }


    public function nullRatioAction(): JsonModel
    {
        $return = Module::newAppJsonModel()->setStatusError();
        try {
            /**
             * @todo implement correct counting
             */
            $countOfInstrumentWithBothBidAskNull = 0;
            $countOfAllInstruments = 1;
        }
        catch (MongodbException $exception) {
            $this->getResponse()->setStatusCode(500);
            return $return
                ->setMessage('error occurred')
                ->setThrowableFromSolvians($exception);
        }
        catch (\Throwable $exception) {
            $this->getResponse()->setStatusCode(503);
            return $return
                ->setMessage('server error occurred')
                ->setThrowableFromSolvians($exception);
        }
        $this->getResponse()->setStatusCode(200);
        return $return->setStateStatusOk()->setData(
            $countOfInstrumentWithBothBidAskNull / $countOfAllInstruments * 100
        );
    }
}