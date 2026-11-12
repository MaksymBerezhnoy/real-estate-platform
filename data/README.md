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

## OpenStreetMap geography

The generator places every property on a real Kyiv street, inside the right district and never
in the water. The reference geography for that lives in [osm/](osm/) and is small enough to be
stored in Git:

| File | Content |
| --- | --- |
| [osm/districts.geojson](osm/districts.geojson) | The 10 administrative districts of Kyiv |
| [osm/water.geojson](osm/water.geojson) | The Dnipro with its bays and channels, lakes, reservoirs and ponds larger than 1 ha |
| [osm/streets.geojson](osm/streets.geojson) | Named urban streets, one feature per street and district |

All files are GeoJSON `FeatureCollection`s with coordinates in WGS 84, `[longitude, latitude]`,
rounded to 5 decimal places (about 1 m). Every file has a top-level `metadata` object with the
description, attribution, license, export date (`exported_at`, UTC) and the simplification
tolerance in meters. Each feature is written on its own line, so a re-export gives a readable
diff.

### Updating

```bash
php bin/export-osm.php
```

The script queries the public [Overpass API](https://wiki.openstreetmap.org/wiki/Overpass_API)
(with fallback mirrors, a pause between requests and backoff when a server is busy), assembles
the polygons, simplifies them, writes the three files and then runs the checks. Useful options:

- `--cache=DIR` keeps the raw Overpass responses in `DIR` and reuses them on the next run, so the
  processing can be tuned without querying the API again;
- `--check` only checks the files already in `data/osm/`.

The checks fail the run when there are not exactly 10 districts, a district has no name or no
streets, districts overlap, a known street is in the wrong district, or a point in the middle of
the Dnipro is not water.

### Fields

`districts.geojson`, geometry `MultiPolygon`:

| Field | Meaning |
| --- | --- |
| `id`, `slug` | Stable Latin identifier, e.g. `shevchenkivskyi`; use it as the district key |
| `name_uk`, `name_en` | Names from the OSM `name:uk` (or `name`) and `name:en` tags |
| `osm_id` | Source relation, e.g. `relation/1755014` |
| `area_km2` | Area of the simplified polygon |

`water.geojson`, geometry `MultiPolygon`, sorted by area:

| Field | Meaning |
| --- | --- |
| `osm_id` | Source way or relation |
| `kind` | OSM `water` value (`river`, `lake`, `pond`, `reservoir`, `oxbow`, ...) or `river` / `reservoir` for older tagging |
| `name_uk`, `name_en` | Names when OSM has them, otherwise empty |
| `area_km2` | Area inside the city bounding box |

The Dnipro polygons extend far beyond Kyiv in OSM, so all water is clipped to the bounding box of
the city boundary.

`streets.geojson`, geometry `MultiLineString`:

| Field | Meaning |
| --- | --- |
| `district` | District slug |
| `name_uk` | Ukrainian name from `name:uk` (or `name`), also the street key |
| `name_en` | English name |
| `name_en_source` | `osm` when the name comes from the `name:en` tag, `transliteration` when the script built it |
| `highway` | The most important OSM class among the street's ways |

Only these OSM classes are kept: `trunk`, `primary`, `secondary`, `tertiary`, `unclassified`,
`residential`, `living_street`. Motorways, link roads, service roads, tracks, footways and
pedestrian zones are left out. A street that crosses several districts appears once in each of
them with only the part inside that district: the street's points are tested against the
simplified district polygons, the same ones the generator uses.

When OSM has no `name:en`, the English name is not translated by hand: the proper part of the
name is transliterated by the official Ukrainian system (Cabinet of Ministers resolution No. 55
of 2010) and the street type word is replaced with its usual English equivalent, so
`вулиця Юрія Іллєнка` becomes `Yuriia Illienka Street`. Such names are marked with
`name_en_source: transliteration`.

### License

The data is © [OpenStreetMap contributors](https://www.openstreetmap.org/copyright) and is
available under the [Open Database License (ODbL) 1.0](https://opendatacommons.org/licenses/odbl/1-0/).
The files in `data/osm/` are a derived database distributed under the ODbL, not under the GPL
that covers the code of this repository. Any public map or page built from them must credit
"© OpenStreetMap contributors".

## Fonts

The theme serves its two typefaces from its own folder,
[web/app/themes/mb-real-estate-theme/assets/fonts/](../web/app/themes/mb-real-estate-theme/assets/fonts/),
and registers them through `fontFace` in `theme.json`. Pages make no requests to Google Fonts.

| Typeface | Use | Files | Source | License |
| --- | --- | --- | --- | --- |
| Inter | Body text | `inter/inter-{latin,cyrillic,cyrillic-ext}.woff2` | [Google Fonts](https://fonts.google.com/specimen/Inter), upstream [rsms/inter](https://github.com/rsms/inter) | [SIL OFL 1.1](../web/app/themes/mb-real-estate-theme/assets/fonts/inter/OFL.txt) |
| Manrope | Headings and prices | `manrope/manrope-{latin,cyrillic,cyrillic-ext}.woff2` | [Google Fonts](https://fonts.google.com/specimen/Manrope), upstream [googlefonts/manrope](https://github.com/googlefonts/manrope) | [SIL OFL 1.1](../web/app/themes/mb-real-estate-theme/assets/fonts/manrope/OFL.txt) |

Each file is a variable font (Inter weights 100-900, Manrope 200-800) cut by Google Fonts to one
Unicode subset. Only three subsets are kept: `latin` and `cyrillic` cover English and Ukrainian,
`cyrillic-ext` carries the hryvnia sign `₴`. Every `fontFace` entry declares the subset's
`unicodeRange`, so a browser downloads a file only when the page contains a character from it.
The six files take 134 KB in total. The `OFL.txt` license texts were taken from the
[google/fonts](https://github.com/google/fonts) repository and sit next to the files.

The files were downloaded on 2026-11-12 from `fonts.gstatic.com` (Inter v20, Manrope v23), the
URLs that the Google Fonts CSS API returns for `Inter:wght@100..900` and `Manrope:wght@200..800`.
Italics are not included: the prototype does not use them.

## Property descriptions

[descriptions/descriptions.json](descriptions/descriptions.json) holds 350 descriptions of
fictional properties for sale, each in Ukrainian and English: 230 apartments and 120 houses. The
generator picks a text for every property by its tags, so full-text search runs over hundreds of
different texts instead of a handful of templates.

**The texts were written by a language model (Claude) for this repository and proofread
selectively, not record by record. They are not taken from real listings, and every property in
them is fictional.** The same note is in the `metadata.generated_note` field of the file.

The texts never state what the generator decides itself: price, exact area, floor number,
address, district or agency. A text names the room count only when its `rooms` tag allows exactly
that number. They contain no digits at all, no street,
district or metro names, no real residential complexes, developers, agencies or people, and no
phones or links. Location is described only in neutral terms such as "metro and a park nearby".

### Format

The file is a JSON object with a `metadata` block and a `descriptions` array, one record per line
for a readable diff. `metadata` defines everything the tags refer to: size class boundaries,
the room and size combinations the generator produces, conditions, floor claims and the feature
vocabulary.

```json
{"id":"apt-001","type":"apartment","rooms":[1,1],"sizes":["xs"],"condition":"new_shell","floor":null,"storeys":null,"features":["open_plan","shelter","metro","quiet"],"uk":"...","en":"..."}
```

| Field | Meaning |
| --- | --- |
| `id` | Stable identifier, `apt-001` ... `apt-230`, `house-001` ... `house-120`; never renumbered |
| `type` | `apartment` or `house` |
| `rooms` | `[min, max]` room counts the text fits; a studio counts as one room |
| `sizes` | Size classes the text fits. Apartments: `xs` < 35 m², `s` 35-55, `m` 55-80, `l` 80-120, `xl` 120+. Houses: `s` < 120 m², `m` 120-200, `l` 200-300, `xl` 300+ |
| `condition` | `new_shell` (new build without finish), `new_finished` (new build with finish), `renovated`, `lived_in`, `needs_repair` |
| `floor` | Apartments only: `null` when the text says nothing about the floor, otherwise `low`, `high` or `top` |
| `storeys` | Houses only: `[min, max]` storeys of the house the text fits |
| `features` | What the text mentions, from the vocabulary in `metadata.features`: balcony, terrace, parking, garden, own boiler, backup power, shelter and so on |
| `uk`, `en` | The text, 60-160 words in each language, one paragraph |

A text fits a property when the type matches, the room count is inside `rooms`, the size class
of the area is in `sizes`, the condition matches, and the floor or storeys do not contradict the
tag. The features of the chosen text are then copied to the property, so the description and the
characteristics block never disagree.

### Coverage

Every room and size combination the generator produces has at least ten matching texts before
filtering by condition:

| Apartment rooms | xs | s | m | l | xl |
| --- | --- | --- | --- | --- | --- |
| 1 | **55** | **70** | 43 | 10 | 0 |
| 2 | 29 | **78** | **104** | 38 | 10 |
| 3 | 24 | 36 | **91** | **77** | 14 |
| 4 | 10 | 21 | 59 | **82** | **33** |
| 5 | 0 | 0 | 10 | **33** | **40** |

| House rooms | s | m | l | xl |
| --- | --- | --- | --- | --- |
| 2 | **26** | 10 | 0 | 0 |
| 3 | **46** | **45** | 8 | 0 |
| 4 | **32** | **61** | **33** | 0 |
| 5 | 0 | **32** | **57** | **16** |
| 6 | 0 | 14 | **38** | **26** |
| 7 | 0 | 0 | 14 | **24** |

Bold cells are the combinations the generator produces (`metadata.combinations`).

### Checking

```bash
php bin/check-descriptions.php
```

The script fails when the file has fewer than 300 or more than 500 records, an id repeats, a
field is missing or has a value outside the vocabulary, a text is shorter than 60 or longer than
160 words, contains a digit, a currency, an area unit, a link, a district or neighbourhood name,
the Dnipro or the city centre, when the English text has Cyrillic or the Ukrainian text has Latin
words or Russian letters, when two texts in the same language are identical, start with the same
four words or share more than 20% of their five-word phrases, or when a room and size combination
has fewer than ten texts. It also prints the distribution by type, condition, length and feature
and the coverage tables above. `--phrases` lists the most repeated four-word phrases, which helps
to spot clichés after editing.

### License

The texts are part of this repository and are distributed under its license.
