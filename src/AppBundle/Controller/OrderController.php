<?php

namespace AppBundle\Controller;

use AppBundle\Constants\Constants;
use AppBundle\Entity\Addresses;
use AppBundle\Entity\Customers;
use AppBundle\Entity\Orders;
use AppBundle\Entity\Orders_Products;
use AppBundle\Entity\Product;
use AppBundle\Form\AddressesType;
use AppBundle\Form\CustomersType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends Controller
{
    /**
     * @Route("/create-order", name="order")
     * @Method("GET")
     * @Template()
     */
    public function orderAction ()
    {
        $basket = $this->get('app.basket');
        $basket->refresh();

        if ($basket->subTotal() !== $basket->getStockCost() || ! $basket->subTotal())
        {
            return $this->redirectToRoute('show_cart');
        }

        $addressesForm = $this->createForm(AddressesType::class);

        $customerForm = $this->createForm(CustomersType::class);

        return [
            'addressesForm' => $addressesForm->createView(),
            'customerForm' => $customerForm->createView(),
        ];
    }

    /**
     * @Route("/create-order", name="create_order")
     * @Method("POST")
     */
    public function createAction(Request $request)
    {
        $basket = $this->get('app.basket');
        $basket->refresh();

        $address = new Addresses();
        $addressesForm = $this->createForm(AddressesType::class, $address);

        $customer = new Customers();
        $customerForm = $this->createForm(CustomersType::class, $customer);

        $addressesForm->handleRequest($request);
        $customerForm->handleRequest($request);

        if ($addressesForm->isValid() && $customerForm->isValid()) {

            $user = $this->get('security.token_storage')->getToken()->getUser();

            if ($user  == Constants::ANONYMOUS) {
                $this->addFlash('error', 'For now you can not buy item, if you are not sign.');
                return $this->redirectToRoute('all_products');
            }

            // Regenerate unique hash identity
            $hash = bin2hex(random_bytes(32));
            $dateTimeNow = new \DateTime('now');

            $repositoryAddress = $this->getDoctrine()->getRepository(Addresses::class);
            $repositoryCustomer = $this->getDoctrine()->getRepository(Customers::class);

            $addressDb = $repositoryAddress->findOneBy([
                'address1' => $address->getAddress1(),
                'address2' => $address->getAddress2(),
                'city' => $address->getCity(),
                'postalCode' => $address->getPostalCode()
            ]);

            $customerDb = $repositoryCustomer->findOneBy([
                'name' => $customer->getName(),
                'email' => $customer->getEmail()
            ]);

            if ($addressDb) {
                $address = $addressDb;
                $address->setUpdatedAt($dateTimeNow);
            } else {
                $address->setCreatedAt($dateTimeNow);
                $address->setUpdatedAt($dateTimeNow);
            }

            if ($customerDb) {
                $customer = $customerDb;
                $customer->setUpdatedAt($dateTimeNow);
            } else {
                $customer->setCreatedAt($dateTimeNow);
                $customer->setUpdatedAt($dateTimeNow);
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($address);
            $entityManager->persist($customer);

            $order = new Orders();
            $order->setHash($hash);
            $order->setPaid(false);
            $order->setTotal($basket->subTotalAll());
            $order->setCustomerId($customer);
            $order->setAddress($address);
            $order->setUser($user);
            $order->setCreatedAt($dateTimeNow);
            $order->setUpdatedAt($dateTimeNow);
            $entityManager->persist($order);

            /**
             * @var Product[] $allItems
             */
            $allItems = $basket->all();

            foreach ($allItems as $item) {
                $ordersProduct = new Orders_Products();
                $ordersProduct->setOrderId($order);
                $ordersProduct->setProductId($item);
                $ordersProduct->setQuantity($item->getQuantity());
                $entityManager->persist($ordersProduct);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Successfully payment');
            return $this->redirectToRoute('all_products');
        }

        return $this->render('@App/Order/order.html.twig', [
            'addressesForm' => $addressesForm->createView(),
            'customerForm' => $customerForm->createView(),
        ]);
    }
}