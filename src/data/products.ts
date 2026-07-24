import type { Product } from '@/types/product';

type InventoryProduct = Pick<Product, 'slug' | 'name' | 'category' | 'collection' | 'colors' | 'available' | 'featured'> & {
  image: string;
  price: number;
};

const formatPrice = (price: number) =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(price);

const inventory: InventoryProduct[] = [
  {
    "slug": "plantas-ave-de-paraiso",
    "name": "Ave de paraiso",
    "category": "plantas",
    "collection": "Plantas",
    "image": "/product-images/plantas-ave-de-paraiso.png",
    "price": 999.0,
    "colors": [],
    "available": true,
    "featured": true
  },
  {
    "slug": "plantas-palma-areca-hb",
    "name": "Palma areca hb",
    "category": "plantas",
    "collection": "Plantas",
    "image": "/product-images/plantas-palma-areca-hb.png",
    "price": 1699.0,
    "colors": [],
    "available": true,
    "featured": false
  },
  {
    "slug": "plantas-palma-areca-aa",
    "name": "Palma areca aa",
    "category": "plantas",
    "collection": "Plantas",
    "image": "/product-images/plantas-palma-areca-aa.png",
    "price": 999.0,
    "colors": [],
    "available": true,
    "featured": false
  },
  {
    "slug": "plantas-olivo-fa",
    "name": "Olivo fa",
    "category": "plantas",
    "collection": "Plantas",
    "image": "/product-images/plantas-olivo-fa.png",
    "price": 1479.0,
    "colors": [],
    "available": true,
    "featured": false
  },
  {
    "slug": "plantas-olivo-ga",
    "name": "Olivo ga",
    "category": "plantas",
    "collection": "Plantas",
    "image": "/product-images/plantas-olivo-ga.png",
    "price": 1459.0,
    "colors": [],
    "available": true,
    "featured": false
  },
  {
    "slug": "plantas-olivo-eb",
    "name": "Olivo eb",
    "category": "plantas",
    "collection": "Plantas",
    "image": "/product-images/plantas-olivo-eb.png",
    "price": 1669.0,
    "colors": [],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-onega",
    "name": "Onega",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-onega.png",
    "price": 79200.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": true
  },
  {
    "slug": "aluminio-onega-esquinero",
    "name": "Onega esquinero",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-onega-esquinero.png",
    "price": 92290.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-sillon-tri-y-mesa",
    "name": "Sillon tri y mesa",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-sillon-tri-y-mesa.png",
    "price": 45670.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-caspio",
    "name": "Caspio",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-caspio.png",
    "price": 48700.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-caspio-esquinero",
    "name": "Caspio esquinero",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-caspio-esquinero.png",
    "price": 71670.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-silla-caspio",
    "name": "Silla caspio",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-silla-caspio.png",
    "price": 6400.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-sillones-casio",
    "name": "Sillones casio",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-sillones-casio.png",
    "price": 25000.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-mesa-volga-1-6",
    "name": "Mesa volga 1.6",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-mesa-volga-1-6.png",
    "price": 25740.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-mesa-volga-2-3",
    "name": "Mesa volga 2.3",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-mesa-volga-2-3.png",
    "price": 29570.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-camastro-sena",
    "name": "Camastro sena",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-camastro-sena.png",
    "price": 21980.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-malaui",
    "name": "Malaui",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-malaui.png",
    "price": 58810.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-silla-malaui",
    "name": "Silla malaui",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-silla-malaui.png",
    "price": 7260.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-mesa-elba",
    "name": "Mesa elba",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-mesa-elba.png",
    "price": 29570.0,
    "colors": [
      {
        "id": "beige",
        "name": "Beige",
        "hex": "#c8b18a",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-mesa-erie",
    "name": "Mesa erie",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-mesa-erie.png",
    "price": 20460.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-silla-erie",
    "name": "Silla erie",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-silla-erie.png",
    "price": 4750.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-ontario-gris",
    "name": "Ontario gris",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-ontario-gris.png",
    "price": 81150.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-ontario-beige",
    "name": "Ontario beige",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-ontario-beige.png",
    "price": 81150.0,
    "colors": [
      {
        "id": "beige",
        "name": "Beige",
        "hex": "#c8b18a",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-sillon-ind-ontario-gris",
    "name": "Sillon ind ontario gris",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-sillon-ind-ontario-gris.png",
    "price": 19980.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-sillon-ind-onrario-beige",
    "name": "Sillon ind onrario beige",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-sillon-ind-onrario-beige.png",
    "price": 19980.0,
    "colors": [
      {
        "id": "beige",
        "name": "Beige",
        "hex": "#c8b18a",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-llona-4-ax",
    "name": "Llona 4 ax",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-llona-4-ax.png",
    "price": 63200.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-llona-5-ax",
    "name": "Llona 5 ax",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-llona-5-ax.png",
    "price": 75800.0,
    "colors": [
      {
        "id": "beige",
        "name": "Beige",
        "hex": "#c8b18a",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-llona-esquinero",
    "name": "Llona esquinero",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-llona-esquinero.png",
    "price": 59400.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-sillon-extra-llona",
    "name": "Sillon extra llona",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-sillon-extra-llona.png",
    "price": 12160.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-llona-mesa-auxiliar",
    "name": "Llona mesa auxiliar",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-llona-mesa-auxiliar.png",
    "price": 4560.0,
    "colors": [
      {
        "id": "beige",
        "name": "Beige",
        "hex": "#c8b18a",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "aluminio-bahia-esquinera",
    "name": "Bahia esquinera",
    "category": "aluminio",
    "collection": "Aluminio",
    "image": "/product-images/aluminio-bahia-esquinera.png",
    "price": 61800.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "ratan-madrid",
    "name": "Madrid",
    "category": "ratan",
    "collection": "Ratan",
    "image": "/product-images/ratan-madrid.png",
    "price": 51500.0,
    "colors": [
      {
        "id": "beige",
        "name": "Beige",
        "hex": "#c8b18a",
        "available": true
      },
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": true
  },
  {
    "slug": "ratan-barcelona-2",
    "name": "Barcelona 2",
    "category": "ratan",
    "collection": "Ratan",
    "image": "/product-images/ratan-barcelona-2.png",
    "price": 22500.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "ratan-barcelona-4",
    "name": "Barcelona 4",
    "category": "ratan",
    "collection": "Ratan",
    "image": "/product-images/ratan-barcelona-4.png",
    "price": 45000.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "ratan-barcelona-6",
    "name": "Barcelona 6",
    "category": "ratan",
    "collection": "Ratan",
    "image": "/product-images/ratan-barcelona-6.png",
    "price": 65000.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "ratan-sevilla",
    "name": "Sevilla",
    "category": "ratan",
    "collection": "Ratan",
    "image": "/product-images/ratan-sevilla.png",
    "price": 33500.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "ratan-bilbao",
    "name": "Bilbao",
    "category": "ratan",
    "collection": "Ratan",
    "image": "/product-images/ratan-bilbao.png",
    "price": 39500.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "ratan-malaga",
    "name": "Malaga",
    "category": "ratan",
    "collection": "Ratan",
    "image": "/product-images/ratan-malaga.png",
    "price": 58000.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "ratan-malaga-petit",
    "name": "Malaga petit",
    "category": "ratan",
    "collection": "Ratan",
    "image": "/product-images/ratan-malaga-petit.png",
    "price": 49500.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "ratan-marbella",
    "name": "Marbella",
    "category": "ratan",
    "collection": "Ratan",
    "image": "/product-images/ratan-marbella.png",
    "price": 49500.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "ratan-granada",
    "name": "Granada",
    "category": "ratan",
    "collection": "Ratan",
    "image": "/product-images/ratan-granada.png",
    "price": 23999.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "ratan-ibiza",
    "name": "Ibiza",
    "category": "ratan",
    "collection": "Ratan",
    "image": "/product-images/ratan-ibiza.png",
    "price": 72500.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-silla-narciso",
    "name": "Silla narciso",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-silla-narciso.png",
    "price": 1199.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      },
      {
        "id": "greie",
        "name": "Greie",
        "hex": "#b9ad9d",
        "available": true
      },
      {
        "id": "cafe",
        "name": "Caf\u00e9",
        "hex": "#c8b18a",
        "available": true
      },
      {
        "id": "verde",
        "name": "Verde",
        "hex": "#587f45",
        "available": false
      },
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": false
      }
    ],
    "available": true,
    "featured": true
  },
  {
    "slug": "plastico-narciso-con-aopyabrazos",
    "name": "Narciso con aopyabrazos",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-narciso-con-aopyabrazos.jpg",
    "price": 1299.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      },
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      },
      {
        "id": "verde",
        "name": "Verde",
        "hex": "#587f45",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-silla-zinnia",
    "name": "Silla zinnia",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-silla-zinnia.jpg",
    "price": 1499.0,
    "colors": [
      {
        "id": "azul-turquesa",
        "name": "Azul turquesa",
        "hex": "#1aa6a8",
        "available": true
      },
      {
        "id": "mostaza",
        "name": "Mostaza",
        "hex": "#c4972f",
        "available": true
      },
      {
        "id": "negra",
        "name": "Negra",
        "hex": "#111111",
        "available": true
      },
      {
        "id": "verde-livo",
        "name": "Verde livo",
        "hex": "#6f7a46",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-silla-dalia",
    "name": "Silla dalia",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-silla-dalia.png",
    "price": 1799.0,
    "colors": [
      {
        "id": "ginger",
        "name": "Ginger",
        "hex": "#b26a37",
        "available": true
      },
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-silla-peonia",
    "name": "Silla peonia",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/client-images/decoracion/decoracion-11.jpg",
    "price": 1699.0,
    "colors": [
      {
        "id": "chocolate",
        "name": "Chocolate",
        "hex": "#4b2b20",
        "available": true
      },
      {
        "id": "khaki",
        "name": "Khaki",
        "hex": "#9c8f6f",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-silla-gerbera",
    "name": "Silla gerbera",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-silla-gerbera.png",
    "price": 1699.0,
    "colors": [
      {
        "id": "marco-gris-asiento-cafe",
        "name": "Marco gris, asiento caf\u00e9",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-gerbera-con-apoyabrazos",
    "name": "Gerbera con apoyabrazos",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/client-images/decoracion/decoracion-12.jpg",
    "price": 1799.0,
    "colors": [
      {
        "id": "marco-gris-asiento-kakhi",
        "name": "Marco gris,asiento kakhi",
        "hex": "#9c8f6f",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-silla-calendula",
    "name": "Silla calendula",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-silla-calendula.png",
    "price": 1899.0,
    "colors": [
      {
        "id": "mostaza",
        "name": "Mostaza",
        "hex": "#c4972f",
        "available": true
      },
      {
        "id": "rojo-carmin",
        "name": "Rojo carmin",
        "hex": "#9b1f2e",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-silla-alta-narciso",
    "name": "Silla alta narciso",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-silla-alta-narciso.jpg",
    "price": 1499.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      },
      {
        "id": "verde",
        "name": "Verde",
        "hex": "#587f45",
        "available": true
      },
      {
        "id": "greige",
        "name": "Greige",
        "hex": "#b9ad9d",
        "available": true
      },
      {
        "id": "gris-oscuro",
        "name": "Gris oscuro",
        "hex": "#4b4b48",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-silla-alta-zinnia",
    "name": "Silla alta zinnia",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-silla-alta-zinnia.jpg",
    "price": 1699.0,
    "colors": [
      {
        "id": "azul-turquesa",
        "name": "Azul turquesa",
        "hex": "#1aa6a8",
        "available": true
      },
      {
        "id": "mostaza",
        "name": "Mostaza",
        "hex": "#c4972f",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-silla-alta-datura",
    "name": "Silla alta datura",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-silla-alta-datura.jpg",
    "price": 2299.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      },
      {
        "id": "beige",
        "name": "Beige",
        "hex": "#c8b18a",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-sillones-narciso",
    "name": "Sillones narciso",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-sillones-narciso.jpg",
    "price": 1699.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      },
      {
        "id": "gris-oscuro",
        "name": "Gris oscuro",
        "hex": "#4b4b48",
        "available": true
      },
      {
        "id": "greige",
        "name": "Greige",
        "hex": "#b9ad9d",
        "available": true
      },
      {
        "id": "verde-militar",
        "name": "Verde militar",
        "hex": "#59633f",
        "available": true
      },
      {
        "id": "verde-olivo",
        "name": "Verde olivo",
        "hex": "#6f7a46",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-banca-narciso",
    "name": "Banca narciso",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-banca-narciso.jpg",
    "price": 3899.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      },
      {
        "id": "gris-oscuro",
        "name": "Gris oscuro",
        "hex": "#4b4b48",
        "available": true
      },
      {
        "id": "greige",
        "name": "Greige",
        "hex": "#b9ad9d",
        "available": true
      },
      {
        "id": "verde-militar",
        "name": "Verde militar",
        "hex": "#59633f",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-mecedra-datura",
    "name": "Mecedra datura",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/client-images/decoracion/decoracion-13.jpg",
    "price": 2099.0,
    "colors": [
      {
        "id": "cafe",
        "name": "Caf\u00e9",
        "hex": "#c8b18a",
        "available": true
      },
      {
        "id": "azul-turquesa",
        "name": "Azul turquesa",
        "hex": "#1aa6a8",
        "available": true
      },
      {
        "id": "verde-militar",
        "name": "Verde militar",
        "hex": "#59633f",
        "available": false
      },
      {
        "id": "mostaza",
        "name": "Mostaza",
        "hex": "#c4972f",
        "available": false
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-mecedora-iris",
    "name": "Mecedora iris",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-mecedora-iris.png",
    "price": 3399.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-silleta-iris",
    "name": "Silleta iris",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-silleta-iris.png",
    "price": 2899.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-sillon-anturio",
    "name": "Sillon anturio",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-sillon-anturio.jpg",
    "price": 2249.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-mesa-auxiliar-iris",
    "name": "Mesa auxiliar iris",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-mesa-auxiliar-iris.png",
    "price": 1399.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-mesa-rectangular-narciso",
    "name": "Mesa rectangular narciso",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-mesa-rectangular-narciso.jpg",
    "price": 1999.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      },
      {
        "id": "gris-oscuro",
        "name": "Gris oscuro",
        "hex": "#4b4b48",
        "available": true
      },
      {
        "id": "greige",
        "name": "Greige",
        "hex": "#b9ad9d",
        "available": true
      },
      {
        "id": "verde-militar",
        "name": "Verde militar",
        "hex": "#59633f",
        "available": true
      },
      {
        "id": "verde-olivo",
        "name": "Verde olivo",
        "hex": "#6f7a46",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-mesa-auxiliar-narciso",
    "name": "Mesa auxiliar narciso",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-mesa-auxiliar-narciso.png",
    "price": 1399.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      },
      {
        "id": "gris-oscuro",
        "name": "Gris oscuro",
        "hex": "#4b4b48",
        "available": true
      },
      {
        "id": "greige",
        "name": "Greige",
        "hex": "#b9ad9d",
        "available": true
      },
      {
        "id": "verde-militar",
        "name": "Verde militar",
        "hex": "#59633f",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-aster-mesa-con-almacenamiento",
    "name": "Aster mesa con almacenamiento",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-aster-mesa-con-almacenamiento.png",
    "price": 1799.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      },
      {
        "id": "cafe",
        "name": "Caf\u00e9",
        "hex": "#c8b18a",
        "available": true
      },
      {
        "id": "verde",
        "name": "Verde",
        "hex": "#587f45",
        "available": true
      },
      {
        "id": "mostaza",
        "name": "Mostaza",
        "hex": "#c4972f",
        "available": true
      },
      {
        "id": "azul-oceano",
        "name": "Azul oceano",
        "hex": "#1f5f8b",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-mesa-auxiliar-anturio",
    "name": "Mesa auxiliar anturio",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-mesa-auxiliar-anturio.png",
    "price": 1700.0,
    "colors": [
      {
        "id": "verde-olivo",
        "name": "Verde olivo",
        "hex": "#6f7a46",
        "available": true
      },
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-mesa-cala",
    "name": "Mesa cala",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-mesa-cala.jpg",
    "price": 5499.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      },
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-mesa-comedor-cala",
    "name": "Mesa comedor cala",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-mesa-comedor-cala.jpg",
    "price": 19999.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      },
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-mesa-cala-s-central",
    "name": "Mesa cala s central",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-mesa-cala-s-central.jpg",
    "price": 4499.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      },
      {
        "id": "negra",
        "name": "Negra",
        "hex": "#111111",
        "available": false
      },
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": false
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-loto-mesa-alta",
    "name": "Loto mesa alta",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-loto-mesa-alta.jpg",
    "price": 4399.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-mesa-loto",
    "name": "Mesa loto",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-mesa-loto.jpg",
    "price": 2199.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      },
      {
        "id": "gris-oscuro",
        "name": "Gris oscuro",
        "hex": "#4b4b48",
        "available": true
      },
      {
        "id": "verde-olivo",
        "name": "Verde olivo",
        "hex": "#6f7a46",
        "available": true
      },
      {
        "id": "negra",
        "name": "Negra",
        "hex": "#111111",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-camastro-raflessia",
    "name": "Camastro raflessia",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-camastro-raflessia.jpg",
    "price": 5999.0,
    "colors": [
      {
        "id": "gris-oscuro",
        "name": "Gris oscuro",
        "hex": "#4b4b48",
        "available": true
      },
      {
        "id": "blanco",
        "name": "Blanco",
        "hex": "#f8f5ec",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-set-craspedia",
    "name": "Set craspedia",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-set-craspedia.png",
    "price": 17899.0,
    "colors": [
      {
        "id": "amarillo-4-pax",
        "name": "Amarillo 4 pax.",
        "hex": "#d7b72f",
        "available": true
      },
      {
        "id": "amarillo-5-pax",
        "name": "Amarillo 5 pax.",
        "hex": "#d7b72f",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-set-iris",
    "name": "Set iris",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-set-iris.png",
    "price": 17899.0,
    "colors": [
      {
        "id": "naranja-4-pax",
        "name": "Naranja 4 pax.",
        "hex": "#d46f2c",
        "available": true
      },
      {
        "id": "blanco-5-pax",
        "name": "Blanco 5 pax.",
        "hex": "#f8f5ec",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-mesa-nilo-2m",
    "name": "Mesa nilo 2m",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-mesa-nilo-2m.png",
    "price": 19850.0,
    "colors": [
      {
        "id": "beige",
        "name": "Beige",
        "hex": "#c8b18a",
        "available": true
      },
      {
        "id": "blabca",
        "name": "Blabca",
        "hex": "#f8f5ec",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-mesa-nilo-1-6m",
    "name": "Mesa nilo 1.6m",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-mesa-nilo-1-6m.png",
    "price": 15850.0,
    "colors": [
      {
        "id": "beige",
        "name": "Beige",
        "hex": "#c8b18a",
        "available": true
      },
      {
        "id": "blabca",
        "name": "Blabca",
        "hex": "#f8f5ec",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-mesa-licerna",
    "name": "Mesa licerna",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-mesa-licerna.png",
    "price": 11650.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      },
      {
        "id": "negra",
        "name": "Negra",
        "hex": "#111111",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "plastico-mesa-leman",
    "name": "Mesa leman",
    "category": "plastico",
    "collection": "Plastico",
    "image": "/product-images/plastico-mesa-leman.png",
    "price": 18650.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      },
      {
        "id": "negra",
        "name": "Negra",
        "hex": "#111111",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "outlet-mecedora-girona",
    "name": "Mecedora girona",
    "category": "outlet",
    "collection": "Outlet",
    "image": "/product-images/outlet-mecedora-girona.png",
    "price": 7999.0,
    "colors": [
      {
        "id": "beige",
        "name": "Beige",
        "hex": "#c8b18a",
        "available": true
      },
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "outlet-camastro-victoria",
    "name": "Camastro victoria",
    "category": "outlet",
    "collection": "Outlet",
    "image": "/product-images/outlet-camastro-victoria.png",
    "price": 7999.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "outlet-turkana",
    "name": "Turkana",
    "category": "outlet",
    "collection": "Outlet",
    "image": "/product-images/outlet-turkana.png",
    "price": 42999.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "outlet-nyasa",
    "name": "Nyasa",
    "category": "outlet",
    "collection": "Outlet",
    "image": "/product-images/outlet-nyasa.png",
    "price": 29200.0,
    "colors": [
      {
        "id": "beige",
        "name": "Beige",
        "hex": "#c8b18a",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "outlet-ladoga",
    "name": "Ladoga",
    "category": "outlet",
    "collection": "Outlet",
    "image": "/product-images/outlet-ladoga.png",
    "price": 42999.0,
    "colors": [
      {
        "id": "blanca",
        "name": "Blanca",
        "hex": "#f8f5ec",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  },
  {
    "slug": "outlet-liena",
    "name": "Liena",
    "category": "outlet",
    "collection": "Outlet",
    "image": "/product-images/outlet-liena.png",
    "price": 42999.0,
    "colors": [
      {
        "id": "gris",
        "name": "Gris",
        "hex": "#8b8b84",
        "available": true
      }
    ],
    "available": true,
    "featured": false
  }
];

export const products: Product[] = inventory.map((product, index) => ({
  id: `product-${product.slug}`,
  slug: product.slug,
  name: product.name,
  shortDescription: `${product.name} disponible en ${product.collection}.`,
  description: `${product.name} forma parte del inventario actual de ${product.collection.toLowerCase()} para espacios exteriores.`,
  mainImage: product.image,
  gallery: [product.image],
  category: product.category,
  categories: [product.category],
  collection: product.collection,
  brand: 'Milapro Home',
  sku: product.slug.toUpperCase(),
  colors: product.colors,
  materials: [],
  dimensions: 'Consultar disponibilidad',
  featured: product.featured,
  available: product.available,
  displayOrder: index + 1,
  tags: [product.category],
  specifications: [{ label: 'Categoria', value: product.collection }],
  price: formatPrice(product.price),
}));
