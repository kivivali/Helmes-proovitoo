<?php

namespace App\DataFixtures;

use App\Entity\Sector;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SectorFixtures extends Fixture
{

    private const SECTORS = [
        'Manufacturing' => [
            'Construction materials' => [],
            'Electronics and Optics' => [],
            'Food and Beverage' => [
                'Bakery & confectionery products' => [],
                'Beverages' => [],
                'Fish & fish products' => [],
                'Meat & meat products' => [],
                'Milk & dairy products' => [],
                'Other' => [],
                'Sweets & snack food' => [],
            ],
            'Furniture' => [
                'Bathroom/sauna' => [],
                'Bedroom' => [],
                "Children's room" => [],
                'Kitchen' => [],
                'Living room' => [],
                'Office' => [],
                'Other (Furniture)' => [],
                'Outdoor' => [],
                'Project furniture' => [],
            ],
            'Machinery' => [
                'Machinery components' => [],
                'Machinery equipment/tools' => [],
                'Manufacture of machinery' => [],
                'Maritime' => [
                    'Aluminium and steel workboats' => [],
                    'Boat/Yacht building' => [],
                    'Ship repair and conversion' => [],
                ],
                'Metal structures' => [],
                'Other' => [],
                'Repair and maintenance service' => [],
            ],
            'Metalworking' => [
                'Construction of metal structures' => [],
                'Houses and buildings' => [],
                'Metal products' => [],
                'Metal works' => [
                    'CNC-machining' => [],
                    'Forgings, Fasteners' => [],
                    'Gas, Plasma, Laser cutting' => [],
                    'MIG, TIG, Aluminum welding' => [],
                ],
            ],
            'Plastic and Rubber' => [
                'Packaging' => [],
                'Plastic goods' => [],
                'Plastic processing technology' => [
                    'Blowing' => [],
                    'Moulding' => [],
                    'Plastics welding and processing' => [],
                ],
                'Plastic profiles' => [],
            ],
            'Printing' => [
                'Advertising' => [],
                'Book/Periodicals printing' => [],
                'Labelling and packaging printing' => [],
            ],
            'Textile and Clothing' => [
                'Clothing' => [],
                'Textile' => [],
            ],
            'Wood' => [
                'Other (Wood)' => [],
                'Wooden building materials' => [],
                'Wooden houses' => [],
            ],
        ],
        'Other' => [
            'Creative industries' => [],
            'Energy technology' => [],
            'Environment' => [],
        ],
        'Service' => [
            'Business services' => [],
            'Engineering' => [],
            'Information Technology and Telecommunications' => [
                'Data processing, Web portals, E-marketing' => [],
                'Programming, Consultancy' => [],
                'Software, Hardware' => [],
                'Telecommunications' => [],
            ],
            'Tourism' => [],
            'Translation services' => [],
            'Transport and Logistics' => [
                'Air' => [],
                'Rail' => [],
                'Road' => [],
                'Water' => [],
            ],
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        $this->createSectors($manager, self::SECTORS, null);
        $manager->flush();
    }

    private function createSectors(ObjectManager $manager, array $sectors, ?Sector $parent): void
    {
        foreach ($sectors as $name => $children) {
            $sector = new Sector();
            $sector->setName($name);
            $sector->setParent($parent);
            $manager->persist($sector);

            $this->createSectors($manager, $children, $sector);
        }
    }
}
