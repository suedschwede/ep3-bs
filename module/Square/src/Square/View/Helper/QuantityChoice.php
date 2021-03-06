<?php



namespace Square\View\Helper;



use Base\Manager\OptionManager;

use Square\Entity\Square;

use Zend\View\Helper\AbstractHelper;



class QuantityChoice extends AbstractHelper

{



    protected $optionManager;



    public function __construct(OptionManager $optionManager)

    {

        $this->optionManager = $optionManager;

    }



    public function __invoke(Square $square, array $bookings)

    {

        $quantityAvailable = $square->need('capacity');



        foreach ($bookings as $booking) {

            $quantityAvailable -= $booking->need('quantity');

        }

        $bookingsCount = count($bookings);

        $view = $this->getView();

        $html = '';



        $html .= '<label for="sb-quantity" style="margin-right: 8px;">';

        $html .= sprintf($view->t('How many %s?'), $this->optionManager->need('subject.square.unit.plural'));

        $html .= '</label>';



        $html .= '<select id="sb-quantity" style="min-width: 64px;">';
        if ($quantityAvailable == 1) {
          $html .= sprintf('<option value="1">1</option>', 1);
        } else {
          $html .= sprintf('<option value="2">2</option>', 1);
          $html .= sprintf('<option value="1">1</option>', 2);
          $html .= sprintf('<option value="4">4</option>', 3);

       }   

       $html .= '</select>';

       //$html .= '<select id="bf-notes" style="min-width: 64px;">';
       //$html .= sprintf('<option value="">keine Vereinsmeisterschaft</option>', 1);
       //$html .= sprintf('<option value="Vereinsmeisterschaft">Vereinsmeisterschaft</option>', 2);
     
        //$html .= '</select>';



        $quantityOccupied = $square->need('capacity') - $quantityAvailable;



        $capacityInfo = $view->squareCapacityInfo($square, $quantityOccupied, 'span');



        if ($capacityInfo) {

            $html .= '<span style="margin-left: 8px;">' . $capacityInfo . '</span>';

        }



        $askNames = $square->getMeta('capacity-ask-names');



        if ($askNames && $quantityAvailable > 1) {

            $askNamesSegments = explode('-', $askNames);



            $html .= '<div class="sb-player-names">';



            $html .= '<div class="separator separator-line"></div>';



            if (isset($askNamesSegments[0]) && $askNamesSegments[0] == 'optional') {

                $html .= sprintf('<p class="sb-player-names-mode gray" data-mode="optional">%s</p>',

                    $this->view->translate('The names of the other players are <b>optional</b>'));

            } else {

                $html .= sprintf('<p class="sb-player-names-mode gray" data-mode="required">%s</p>',

                    $this->view->translate('The names of the other players are <b>required</b>'));

            }



            for ($i = 2; $i <= $quantityAvailable; $i++) {

                $html .= sprintf('<div class="sb-player-name sb-player-name-%s" style="margin-bottom: 4px;">', $i);



                $html .= sprintf('<input type="text" name="sb-player-name-%1$s" class="ui-autocomplete-input" data-autocomplete-url="/public/backend/user/interpret" id="sb-name-%1$s" autocomplete="off" placeholder="%1$s. %2$s" style="min-width: 160px;">',

                    $i, $this->view->translate('Player\'s name'));



                if (isset($askNamesSegments[2]) && $askNamesSegments[2] == 'email') {



                    $html .= sprintf(' <input type="text" name="sb-player-email-%1$s" id="sb-player-email-%1$s" value="" placeholder="...%2$s" style="min-width: 160px;">',

                        $i, $this->view->translate('and email address'));

                }



                if ((isset($askNamesSegments[2]) && $askNamesSegments[2] == 'phone') ||

                    (isset($askNamesSegments[3]) && $askNamesSegments[3] == 'phone')) {



                    $html .= sprintf(' <input type="text" name="sb-player-phone-%1$s" id="sb-player-phone-%1$s" value="" placeholder="...%2$s" style="min-width: 160px;">',

                        $i, $this->view->translate('and phone number'));

                }



                $html .= '</div>';

            }



            $html .= '</div>';

        }
        

        return $html;

    }



}

