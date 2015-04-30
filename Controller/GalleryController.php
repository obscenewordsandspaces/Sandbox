<?php

namespace AppBundle\Controller;

use AppBundle\Helpers\Paginator;
use Symfony\Component\HttpFoundation\Request;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\Image;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class GalleryController extends Controller
{
    public function indexAction($page, $sortBy, $order)
    {
        $resultsPerPage = 8;
        $startingItem = $resultsPerPage * ($page - 1) ;
        
        $em = $this->getDoctrine()->getManager();
        $countRows = $em->getRepository('AppBundle:Image')->createQueryBuilder('id')->select('COUNT(id)')->getQuery()->getSingleScalarResult();
        $lastPage = ceil($countRows / $resultsPerPage);



        $paginator = new Paginator($page, $countRows, $resultsPerPage);
        $pageList = $paginator->getPagesList();

        $query = $em->getRepository('AppBundle:Image')->findBy(array(), array($sortBy => $order), $resultsPerPage, $startingItem);
  
    	return $this->render('AppBundle:Default:gallery.html.twig', array(
            'title' => 'sandbox|gallery',
            'page' => $page,
            'pageList' => $pageList,
            'content' => $query,
            'sortBy' => $sortBy,
            'order' => $order,
            'lastPage' => $lastPage));
    }
    public function imageAction($id) //single image
    {
        $em = $this->getDoctrine()->getManager();
        $image = $em->getRepository('AppBundle:Image')->findOneBy(['id' => $id]);
        if(!$image){
            throw $this->createNotFoundException('No image with id '.$id);
            }
        return $this->render('AppBundle:Default:image.html.twig', array('title' => 'sandbox|image', 'image' => $image));

    }
    public function imageEditAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $image = $em->getRepository('AppBundle:Image')->findOneBy(['id' => $id]);

        if (false === $this->get('security.authorization_checker')->isGranted('edit', $image)) {
            throw new AccessDeniedException('Unauthorised access!');
            }
            
        $defaultData = array('message' => 'Type your message here');
        $form = $this->createFormBuilder($defaultData)
        ->add('title', 'text')
        ->add('description', 'textarea')
        ->add('Save', 'submit')
        ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $image->setTitle($data['title'])->setDescription($data['description'])->setUpdated(new \Datetime());
            $em->flush();
            return $this->render('AppBundle:Default:image.html.twig', array('title' => 'sandbox|image', 'image' => $image));
            }

        return $this->render('AppBundle:Default:image.html.twig', array('title' => 'sandbox|image', 'image' => $image, 'form' => $form->createView()));

    }
    public function imageDeleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $image = $em->getRepository('AppBundle:Image')->findOneBy(['id' => $id]);

        if (false === $this->get('security.authorization_checker')->isGranted('delete', $image)) {
            throw new AccessDeniedException('Unauthorised access!');
        }
       
        if (!$image) {
                $message = 'No image with this ID';
            }else {
                $em->remove($image);
                $em->flush();
                $fs = new Filesystem();
                $fs->remove( __DIR__.'/../../../../web/images/'.$image->getFileName().'.'.$image->getExtension());
                $fs->remove( __DIR__.'/../../../../web/media/cache/thumb/'.$image->getFileName().'.'.$image->getExtension());
                $message = 'Image deleted.';
            }
        return $this->render('AppBundle:Default:message.html.twig', array('title' => 'sandbox|project', 'message' => $message));
    }
}