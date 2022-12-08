<?php

namespace mod_portfoliobuilder\event;

/**
 * The portfolio_viewed event class.
 *
 * @package     mod_portfoliobuilder
 * @category    event
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class portfolio_viewed extends \core\event\base {
    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'portfoliobuilder';
    }
}
