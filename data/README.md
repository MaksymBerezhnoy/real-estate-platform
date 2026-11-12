# Data sources

Reference data used by the data generator. Every third-party asset is listed here with its
source and license.

## Photos

Property galleries are built from a shared set of stock photos. The image files are not stored
in Git: the repository keeps only the manifest [photos.json](photos.json), and the files are
downloaded into `data/photos/` (gitignored) with:

```bash
bin/download-photos.sh
```

The script skips files that are already present and verifies every download against the
SHA-256 checksum in the manifest. A checksum mismatch means the source file has changed and the
manifest entry has to be reviewed.

Each manifest entry records the file name, the room category, the source page, the author, the
license, the image size and the checksum.

The set has 151 photos, all 1920 px wide:

| Category | Photos | Content |
| --- | --- | --- |
| `apartment-building` | 25 | Facades of multi-storey residential buildings, mostly Kyiv and Eastern Europe |
| `house-facade` | 24 | Private houses: 13 American suburban, 11 European |
| `living-room` | 30 | Living rooms, some with an open kitchen |
| `kitchen` | 24 | Kitchens |
| `bedroom` | 24 | Bedrooms |
| `bathroom` | 20 | Bathrooms |
| `city-panorama` | 4 | Wide city views for the home page: three of Kyiv, one of New York |

### License

All photos come from [Pexels](https://www.pexels.com) and are used under the
[Pexels License](https://www.pexels.com/license/), which allows free use and modification
without attribution. Authors are credited here anyway.

### Credits

| Author | Profile | Photos |
| --- | --- | --- |
| aethel_blanc | https://www.pexels.com/@aethel_blanc-2158367555/ | 1 |
| Alesha | https://www.pexels.com/@alesha-260500796/ | 1 |
| Alex Hrek | https://www.pexels.com/@lxe00/ | 1 |
| Alex Tyson | https://www.pexels.com/@alex-tyson-919593032/ | 2 |
| Anastasiya Pavlova | https://www.pexels.com/@anastasiya-pavlova-17630119/ | 1 |
| Andrew Patrick Photo | https://www.pexels.com/@am83/ | 1 |
| Andrey Matsiievskyi | https://www.pexels.com/@andrey-matsiievskyi-1864204/ | 1 |
| Andriy Nestruiev | https://www.pexels.com/@andriy-nestruiev-288919368/ | 1 |
| ATHENEA CODJAMBASSIS ROSSITTO | https://www.pexels.com/@athenea-codjambassis-rossitto-472760075/ | 1 |
| Atlantic Ambience | https://www.pexels.com/@freestockpro/ | 1 |
| Bingqian Li | https://www.pexels.com/@bingqian-li-230971044/ | 1 |
| Binyamin Mellish | https://www.pexels.com/@binyaminmellish/ | 4 |
| Clay Elliot | https://www.pexels.com/@ibidsy/ | 1 |
| cottonbro studio | https://www.pexels.com/@cottonbro/ | 1 |
| Curtis Adams | https://www.pexels.com/@curtis-adams-1694007/ | 5 |
| Diana Room | https://www.pexels.com/@diana-room-76106124/ | 1 |
| Douglas Rafael Fonseca | https://www.pexels.com/@douglas-rafael-fonseca-66737161/ | 1 |
| Efrem Efre | https://www.pexels.com/@efrem-efre-2786187/ | 1 |
| Elias Storm | https://www.pexels.com/@elias-storm-2159954265/ | 1 |
| Erik Mclean | https://www.pexels.com/@introspectivedsgn/ | 1 |
| Expect Best | https://www.pexels.com/@expect-best-79873/ | 1 |
| Furkan Tumer | https://www.pexels.com/@furkan-tumer-1560109/ | 1 |
| German Engelgardt | https://www.pexels.com/@german-engelgardt-150603118/ | 1 |
| Gustavo Galeano Maz | https://www.pexels.com/@fotografiagmazg/ | 1 |
| I'm Zion | https://www.pexels.com/@zion/ | 1 |
| Image Hunter | https://www.pexels.com/@image-hunter-281453274/ | 1 |
| Jakob Andersson | https://www.pexels.com/@jakobandersson/ | 1 |
| Jakub Zerdzicki | https://www.pexels.com/@jakubzerdzicki/ | 2 |
| Jaycee300s | https://www.pexels.com/@jaycee300s-3059779/ | 1 |
| Karolina K | https://www.pexels.com/@karolina-k-2149378526/ | 1 |
| KHALED KFM | https://www.pexels.com/@khaled-kfm-11440920/ | 1 |
| Lexa Shep | https://www.pexels.com/@lexa-shep-1842481100/ | 1 |
| Lisa Anna | https://www.pexels.com/@lisa-anna-901356985/ | 6 |
| Marcus Lenk | https://www.pexels.com/@marcus-lenk-3854713/ | 1 |
| Marharyta Volkova | https://www.pexels.com/@marharyta-volkova-218031997/ | 1 |
| Mateusz Pielech | https://www.pexels.com/@mateusz-pielech-1032276948/ | 3 |
| Max Vakhtbovych | https://www.pexels.com/@artbovich/ | 70 |
| Mika Andrianarijaona | https://www.pexels.com/@mika-andrianarijaona-123292/ | 1 |
| Mykhailo Volkov | https://www.pexels.com/@movievolkov/ | 4 |
| Nadin Nandin | https://www.pexels.com/@nadin-nandin-134952564/ | 2 |
| Nelson Marulanda | https://www.pexels.com/@nelson-marulanda-708254/ | 1 |
| Nikolay Demirev | https://www.pexels.com/@nikolaydemirev/ | 1 |
| Oleksandr Plakhota | https://www.pexels.com/@oleksandr-plakhota-1270583835/ | 1 |
| Pixabay | https://www.pexels.com/@pixabay/ | 8 |
| Roman Kaiuk🇺🇦 | https://www.pexels.com/@romakaiuk/ | 1 |
| SANDRA REI | https://www.pexels.com/@sandra-rei-27260369/ | 1 |
| Shantae Shaffer | https://www.pexels.com/@shantae-shaffer-815770180/ | 1 |
| shaza jocarlos | https://www.pexels.com/@shaza-jocarlos-841367558/ | 1 |
| SHOX ART | https://www.pexels.com/@shox/ | 1 |
| Tembela Bohle | https://www.pexels.com/@bohlemedia/ | 1 |
| Tiago Chaves | https://www.pexels.com/@tiago-chaves-2154478168/ | 1 |
| Viaceslav Kat | https://www.pexels.com/@fotoaibe/ | 1 |
| Алан Албегов | https://www.pexels.com/@albeg/ | 2 |
| Спиридон Варфаламеев | https://www.pexels.com/@239811708/ | 1 |

The source page of every single photo is in the `page_url` field of the manifest.
