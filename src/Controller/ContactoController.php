<?php

namespace App\Controller;

use App\Entity\Contacto;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\ContactoFormType;
use Symfony\Component\HttpFoundation\Request;

final class ContactoController extends AbstractController
{

    #[Route('/contacto/{codigo}', name: 'contacto', requirements: ['codigo' => '[0-9]+'])]    
    public function ficha(ManagerRegistry $doctrine, int $codigo = 1): Response
    {
        // La primera instrucción suele ser esta, ya que cogemos el repositorio de la entidad asociada
            $repositorio = $doctrine->getRepository(Contacto::class);
        // Ahora usamos uno de los métodos del repositorio
            $contacto = $repositorio->find($codigo);
        return $this->render('ficha.html.twig', [
            'contacto' => $contacto
        ]);
    }

    #[Route('/contacto/nuevo/{nombre}/{telefono}/{email}', name: 'nuevo-con-datos')]
    public function nuevoConDatos(
        ManagerRegistry $doctrine,
        string $nombre,
        string $telefono,
        string $email
    ) {
        // Crear un nuevo contacto
        $contacto = new Contacto();

        // Asignar los datos del contacto
        $contacto->setNombre($nombre);
        $contacto->setTelefono($telefono);
        $contacto->setEmail($email);

        // Guardar el contacto
        $entityManager = $doctrine->getManager();
        $entityManager->persist($contacto);
        $entityManager->flush();

        // Redirigir a la ficha del contacto
        return $this->redirectToRoute('contacto', ["codigo" => $contacto->getId()]);
    }

    #[Route('/contacto/borrar/{codigo}', name: 'borrar')]
    public function borrar(ManagerRegistry $doctrine, int $codigo)
    {
        // Obtenemos el contacto por id
        $contacto = $doctrine->getRepository(Contacto::class)->find($codigo);

        if ($contacto) {
            // Obtenemos el manager
            $entityManager = $doctrine->getManager();
            try {
                // Eliminamos el contacto
                $entityManager->remove($contacto);
                // Hacemos flush
                $entityManager->flush();
                // Redirigimos a inicio para que se actualice la lista
                return $this->redirectToRoute('inicio');
            } catch (\Exception $e) {
                // En una aplicación real, deberíamos mostrar una página de error y hacer el log del error.
                error_log("Error insertando objeto " . $e->getMessage());
                // Si hay error, mostramos un mensaje al usuario
                return new Response("Error insertando objeto " . $e->getMessage());
            }
        } else {
            // Aquí hay que crear una página de error
            return new Response("No se ha encontrado el contacto");
        }
    }

    #[Route('/contacto/nuevo', name: 'nuevo')]
    public function nuevo(ManagerRegistry $doctrine, Request $request)
    {
        $contacto = new Contacto();
        $formulario = $this->createForm(ContactoFormType::class, $contacto);
        $formulario->handleRequest($request);

        if ($formulario->isSubmitted() && $formulario->isValid()) {
            $contacto = $formulario->getData();
            $entityManager = $doctrine->getManager();
            $entityManager->persist($contacto);
            $entityManager->flush();
            return $this->redirectToRoute('contacto', ["codigo" => $contacto->getId()]);
        }
        return $this->render('nuevo.html.twig', array('formulario' => $formulario->createView()));
    }

    #[Route('/contacto/editar/{codigo}', name: 'editar', requirements:["codigo"=>"\d+"])]
    public function editar(ManagerRegistry $doctrine, Request $request, int $codigo) {
        $repositorio = $doctrine->getRepository(Contacto::class);
        //En este caso, los datos los obtenemos del repositorio de contactos
        $contacto = $repositorio->find($codigo);

        if ($contacto){
            // A partir de $contacto, rellena automáticamente el formulario y el resto es igual que para nuevo
            $formulario = $this->createForm(ContactoFormType::class, $contacto);

            $formulario->handleRequest($request);

            if ($formulario->isSubmitted() && $formulario->isValid()) {
                // Guardamos y redirigimos a la ficha
                $contacto = $formulario->getData();
                $entityManager = $doctrine->getManager();
                $entityManager->persist($contacto);
                $entityManager->flush();
                return $this->redirectToRoute('contacto', ["codigo" => $contacto->getId()]);
            }

            // Ponemos los datos del contacto
            return $this->render('editar.html.twig', array(
                'formulario' => $formulario->createView()
            ));

        }else{
            return $this->render('editar.html.twig', [
                'contacto' => NULL
            ]);
        }
    }

}