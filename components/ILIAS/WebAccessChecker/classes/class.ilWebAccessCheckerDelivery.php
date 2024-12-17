<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

use ILIAS\FileDelivery\Delivery;
use ILIAS\HTTP\Cookies\CookieFactory;
use ILIAS\HTTP\Services;
use ILIAS\ResourceStorage\Consumer\StreamAccess\StreamAccess;
use ILIAS\ResourceStorage\Consumer\StreamAccess\StreamInfoFactory;
use ILIAS\ResourceStorage\Consumer\StreamAccess\TokenFactory;

/**
 * Class ilWebAccessCheckerDelivery
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilWebAccessCheckerDelivery
{
    private ilWebAccessChecker $wac;
    private Services $http;
    private string $img_dir;


    public static function run(Services $httpState, CookieFactory $cookieFactory): void
    {
        $obj = new self($httpState, $cookieFactory);
        $obj->handleRequest();
    }


    /**
     * ilWebAccessCheckerDelivery constructor.
     */
    public function __construct(Services $httpState, CookieFactory $cookieFactory)
    {
        $this->wac = new ilWebAccessChecker($httpState, $cookieFactory);
        $this->http = $httpState;
        $this->img_dir = realpath(__DIR__ . '/../templates/images');
    }


    protected function handleRequest(): void
    {
        // Set errorreporting
        ilInitialisation::handleErrorReporting();
        $queries = $this->http->request()->getQueryParams();

        // Set customizing
        if (isset($queries[ilWebAccessChecker::DISPOSITION])) {
            $this->wac->setDisposition($queries[ilWebAccessChecker::DISPOSITION]);
        }
        if (isset($queries[ilWebAccessChecker::STATUS_CODE])) {
            $this->wac->setSendStatusCode($queries[ilWebAccessChecker::STATUS_CODE]);
        }
        if (isset($queries[ilWebAccessChecker::REVALIDATE])) {
            $this->wac->setRevalidateFolderTokens($queries[ilWebAccessChecker::REVALIDATE]);
        }

        // Check if File can be delivered
        try {
            if ($this->wac->check()) {
                $this->deliver();
            } else {
                $this->deny();
            }
        } catch (ilWACException $e) {
            match ($e->getCode()) {
                ilWACException::NOT_FOUND => $this->handleNotFoundError($e),
                ilWACException::ACCESS_DENIED,
                ilWACException::ACCESS_DENIED_NO_PUB,
                ilWACException::ACCESS_DENIED_NO_LOGIN => $this->handleAccessErrors($e),
                default => $this->handleErrors($e),
            };
        }
    }

    /**
     * @throws ilWACException
     */
    protected function deny(): void
    {
        if (!$this->wac->isChecked()) {
            throw new ilWACException(ilWACException::ACCESS_WITHOUT_CHECK);
        }
        throw new ilWACException(ilWACException::ACCESS_DENIED);
    }


    protected function deliverDummyImage(): void
    {
        $ilFileDelivery = new Delivery($this->img_dir . '/access_denied.png', $this->http);
        $ilFileDelivery->setDisposition($this->wac->getDisposition());
        $ilFileDelivery->deliver();
    }


    protected function deliverDummyVideo(): void
    {
        $ilFileDelivery = new Delivery($this->img_dir . '/access_denied.mp4', $this->http);
        $ilFileDelivery->setDisposition($this->wac->getDisposition());
        $ilFileDelivery->stream();
    }

    protected function handleNotFoundError(ilWACException $e): void
    {
        $response = $this->http
            ->response()
            ->withStatus(404);
        $this->http->saveResponse($response);
    }

    protected function handleAccessErrors(ilWACException $e): void
    {
        //1.5.2017 Http code needs to be 200 because mod_xsendfile ignores the response with an 401 code. (possible leak of web path via xsendfile header)
        $response = $this->http
            ->response()
            ->withStatus(200);

        $this->http->saveResponse($response);

        if ($this->wac->getPathObject()->isVideo()) {
            $this->deliverDummyVideo();
        }

        $this->deliverDummyImage();

        $this->wac->initILIAS();
    }


    /**
     * @throws ilWACException
     */
    protected function handleErrors(ilWACException $e): void
    {
        $response = $this->http->response()
            ->withStatus(500);

        /**
         * @var \Psr\Http\Message\StreamInterface $stream
         */
        $stream = $response->getBody();
        $stream->write($e->getMessage());

        $this->http->saveResponse($response);
    }


    /**
     * @throws ilWACException
     */
    protected function deliver(): void
    {
        if (!$this->wac->isChecked()) {
            throw new ilWACException(ilWACException::ACCESS_WITHOUT_CHECK);
        }

        $path = $this->wac->getPathObject();
        // This is currently the place where WAC handles things from the ResourceStorageService.
        if ($path->getModuleType() === 'rs') {
            // initialize constants
            if (!defined('CLIENT_DATA_DIR')) {
                $ini = new ilIniFile("./ilias.ini.php");
                $ini->read();
                $data_dir = rtrim($ini->readVariable("clients", "datadir"), '/');
                $client_data_dir = $data_dir . "/" . $path->getClient();
            } else {
                $client_data_dir = CLIENT_DATA_DIR;
            }

            $token_factory = new TokenFactory($client_data_dir);
            $token = $token_factory->check($path->getFileName());
            $path_to_file = $token->resolveStream(); // FileStream
        } else {
            $path_to_file = $path->getCleanURLdecodedPath();
        }

        $real_path_to_file = realpath(__DIR__ . '/../../../../public/' . $path_to_file);

        $ilFileDelivery = new Delivery($real_path_to_file, $this->http);
        $ilFileDelivery->setCache(true);
        $ilFileDelivery->setDisposition($this->wac->getDisposition());
        if ($path->isStreamable()) { // fixed 0016468
            $ilFileDelivery->stream();
        } else {
            $ilFileDelivery->deliver();
        }
    }
}
