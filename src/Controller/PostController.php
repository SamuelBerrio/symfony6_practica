<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class PostController extends AbstractController
{

    private $em;

    /**
     * @param $em
     */

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/post/{id}', name: 'app_post')]
    public function index($id): Response
    {
        $posts = $this->em->getRepository(Post::class)->find(1);
        $custom_post = $this->em->getRepository(Post::class)->findPost($id);
        return $this->render('post/index.html.twig', [
            'posts' => $posts,
            'custom_post' => $custom_post
        ]);
    }

    #[Route('/insert/post', name: 'insert_post')]
    public function insert(){

        $user = $this->em->getRepository(User::class)->find(1);

        $post = new Post('Mi post insertado 2','Opinion','Hola mundo','hola.jpg','XDDD');
        $post->setUser($user);
        $this->em->persist($post);
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/update/post', name: 'update_post')]
    public function update(){

        $post = $this->em->getRepository(Post::class)->find(4);
        $post->setTitle('Mi nuevo titulo');
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/remove/post', name: 'remove_post')]
    public function remove(){

        $post = $this->em->getRepository(Post::class)->find(4);
        $this->em->remove($post);
        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/', name: 'form_post')]
    public function formPost(Request $request, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $posts = $this->em->getRepository(Post::class)->findAllPosts();
        $form = $this->createForm(PostType::class,$post);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $file = $form->get('file')->getData();
            $url = str_replace(" ","-",$form->get('title')->getData());

            if($file){
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('files_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                   throw new \Exception("Ups problema con tu archivo");
                }

                $post->setFile($newFilename);

            }

            $post->setUrl($url);
            $user = $this->em->getRepository(User::class)->find(1);
            $post->setUser($user);
            $this->em->persist($post);
            $this->em->flush();
            return $this->redirectToRoute('form_post');
        }

        return $this->render('post/index.html.twig', [
            'form' => $form->createView(),
            'posts' => $posts
        ]);
    }
}
