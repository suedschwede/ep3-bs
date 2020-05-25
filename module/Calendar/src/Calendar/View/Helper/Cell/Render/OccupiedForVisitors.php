<?php



namespace Calendar\View\Helper\Cell\Render;



use Square\Entity\Square;

use Zend\View\Helper\AbstractHelper;



class OccupiedForVisitors extends AbstractHelper

{



    public function __invoke(array $reservations, array $cellLinkParams, Square $square, $user = null,$userBooking)

    {

        $view = $this->getView();

        $reservationsCount = count($reservations);
        $nameData ="";
        $cellLabel ="";

        if ($reservationsCount > 1) {
            if ($user) {
              foreach ($reservations as $reservation) {
                $booking = $reservation->needExtra('booking'); 
                if ($cellLabel) {
                   $cellLabel =  $cellLabel . ' / ' .  $booking->needExtra('user')->need('alias');
                 } else {
                      $cellLabel = $booking->needExtra('user')->need('alias');
                 }        
              }
            } else {
              $cellLabel = $this->view->t('Occupied');
            }
            if ($userBooking) {
              return $view->calendarCellLink($this->view->t($cellLabel), $view->url('square', [], $cellLinkParams), 'cc-own');

            } else {
              return $view->calendarCellLink($this->view->t($cellLabel), $view->url('square', [], $cellLinkParams), 'cc-single');
            }

        } else {

            $reservation = current($reservations);

            $booking = $reservation->needExtra('booking');
            $quantity = $booking->need('quantity');
            $playerNames = $booking->getMeta('player-names');
            if ($playerNames) {
              $playerNames = @unserialize($playerNames);
              if ($playerNames) {
                foreach ($playerNames as $playerData) {
                  $nameData = $playerData['value'];
                }

              }
            }

            $prefix = "";
            if ($quantity == 4) {
              $prefix = "Doppel ";
            }

            if ($square->getMeta('public_names', 'false') == 'true') {

                $cellLabel = $booking->needExtra('user')->need('alias');

            } else if ($square->getMeta('private_names', 'false') == 'true' && $user) {
                if ($nameData) {
                  $cellLabel = $prefix . $booking->needExtra('user')->need('alias') . ' / ' . $nameData;
                } else {
                  $cellLabel = $prefix . $booking->needExtra('user')->need('alias');
                }


            } else {

                $cellLabel = null;

            }



            $cellGroup = ' cc-group-' . $booking->need('bid');

            
            switch ($booking->need('status')) {

                case 'single':

                    if (! $cellLabel) {

                        $cellLabel = $this->view->t('Occupied');

                    }

                    if ($userBooking) {
                      return $view->calendarCellLink($cellLabel, $view->url('square', [], $cellLinkParams), 'cc-own' . $cellGroup);
                    } else {
                      return $view->calendarCellLink($cellLabel, $view->url('square', [], $cellLinkParams), 'cc-multiple' . $cellGroup);
                    }

                case 'subscription':

                    if (! $cellLabel) {

                        $cellLabel = $this->view->t('Subscription');

                    }

                    return $view->calendarCellLink($cellLabel, $view->url('square', [], $cellLinkParams), 'cc-multiple' . $cellGroup);

            }

        }

    }



}

