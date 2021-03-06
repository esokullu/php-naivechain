<?php

namespace Blockchain\Naivechain;

use Blockchain\Naivechain\Exceptions\InvalidBlockIndexException;
use Blockchain\Naivechain\Exceptions\InvalidBlockHashException;

class Chain
{

    /**
     * @var Array
     */
    protected $links;

    /**
     * Create the first Block
     * @param GenesisBlock $genesis_block  the first block in the chain
     * @return void
     */
    public function __construct(GenesisBlock $genesis_block)
    {
        $this->links = [$genesis_block];
    }

    /**
     * Gets the blocks on the chain
     * @return Array
     */
    public function getBlocks()
    {
        return $this->links;
    }

    /**
     * Get a single block by the index
     * @param int $index  the index of the block
     * @return Block
     */
    public function getBlock(int $index)
    {
        return isset($this->links[$index]) ? $this->links[$index] : $this->links[0];
    }

    /**
     * Get the newest block
     * @return Block
     */
    public function getLatestBlock()
    {
        return array_slice($this->links, -1)[0];
    }

    /**
     * Get the length of the chain
     * @return int
     */
    public function getBlockLength()
    {
        return count($this->links);
    }

    /**
     * Create the hash for the chain
     * @param string $string  the hashable string of fields to use
     * @return string
     */
    private function createHash(string $string)
    {
        return hash('sha256', $string);
    }

    /**
     * Calculates the hash for a given block
     * @param Block $block  the block to be calculated
     * @return string
     */
    private function calculateHashForBlock(Block $block)
    {
        return $this->createHash($block->getHashableString());
    }

    /**
     * Check if the block is actually valid
     * @param Block $block  the block to check
     * @param Block $previousBlock  the previous block in the chain
     * @return boolean
     */
    private function isValidBlock(Block $block, Block $previousBlock)
    {
        if ($block->getIndex() > 0 && $previousBlock->getHash() !== $block->getPreviousHash()) {
            throw new InvalidBlockHashException('Invalid previous hash');

            return false;
        } elseif ($this->calculateHashForBlock($block) !== $block->getHash()) {
            throw new InvalidBlockHashException('Invalid hash: ' . $this->calculateHashForBlock($block) . ' compared against ' . $block->getHash());

            return false;
        }

        return true;
    }

    /**
     * Check if the new block being added is valid
     * @param type $newBlock  the new block being added
     * @param type $previousBlock  the previous block in the chain
     * @return boolean
     */
    private function isValidNewBlock(Block $newBlock, Block $previousBlock)
    {
        if ($previousBlock->getIndex() + 1 !== $newBlock->getIndex()) {
            throw new InvalidBlockIndexException('Invalid index');

            return false;
        }

        return $this->isValidBlock($newBlock, $previousBlock);
    }

    /**
     * Add a new block to the chain
     * @param Block $block  the block to be added
     * @return boolean
     */
    public function addBlock(Block $block)
    {
        if ($this->isValidNewBlock($block, $this->getLatestBlock())) {
            $this->links[$block->getIndex()] = $block;
            return $block;
        }

        return false;
    }

    /**
     * Checks to see if the chain is valid
     * @return boolean
     */
    public function checkValidity()
    {
        $checks = array_filter($this->getBlocks(), function ($block) {
            return $this->isValidBlock($block, $this->getBlock($block->getIndex() - 1));
        });

        return count($checks) === count($this->getBlocks());
    }

    /**
     * Create a new block with only a string
     * @param string $data  the data for the new block
     * @return Block
     */
    public function createNewBlock(string $data)
    {
        $index = $this->getBlockLength();
        $previousBlock = $this->getLatestBlock();
        $time = time();

        $nextHash = $this->createHash($index . $previousBlock->getHash() . $time . $data);

        $block = new Block($index, $previousBlock->getHash(), $time, $data, $nextHash);

        return $this->addBlock($block);
    }
}
