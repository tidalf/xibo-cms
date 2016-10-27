<?php
/*
 * Spring Signage Ltd - http://www.springsignage.com
 * Copyright (C) 2015 Spring Signage Ltd
 * (Nonce.php)
 */


namespace Xibo\Entity;


use Xibo\Exception\FormExpiredException;
use Xibo\Factory\RequiredFileFactory;
use Xibo\Helper\Random;
use Xibo\Service\LogServiceInterface;
use Xibo\Storage\StorageServiceInterface;

/**
 * Class RequiredFile
 * @package Xibo\Entity
 */
class RequiredFile implements \JsonSerializable
{
    use EntityTrait;
    public $nonce;
    public $expiry;
    public $lastUsed;
    public $displayId;
    public $size;
    public $storedAs;
    public $layoutId;
    public $regionId;
    public $mediaId;
    public $bytesRequested = 0;
    public $complete = 0;

    /** @var  RequiredFileFactory */
    private $requiredFileFactory;

    /**
     * Entity constructor.
     * @param StorageServiceInterface $store
     * @param LogServiceInterface $log
     * @param RequiredFileFactory $requiredFileFactory
     */
    public function __construct($store, $log, $requiredFileFactory)
    {
        $this->setCommonDependencies($store, $log);
        $this->requiredFileFactory = $requiredFileFactory;
    }

    /**
     * @param array $options
     */
    public function save($options = [])
    {
        $options = array_merge([
            'refreshNonce' => true
        ], $options);

        // Always update the nonce when we save
        if ($options['refreshNonce']) {
            $this->lastUsed = 0;
            $this->expiry = time() + 86400;
            $this->nonce = md5(Random::generateString() . SECRET_KEY . time() . $this->layoutId . $this->regionId . $this->mediaId);
        }

        $this->requiredFileFactory->addOrReplace($this, ($this->hasPropertyChanged('nonce') ? $this->getOriginalValue('nonce') : $this->nonce));
    }

    public function isValid()
    {
        if (($this->lastUsed != 0 && $this->bytesRequested > $this->size) || $this->expiry < time())
            throw new FormExpiredException();
    }

    public function markUsed()
    {
        $this->lastUsed = time();
        $this->save(['refreshNonce' => false]);
    }
}