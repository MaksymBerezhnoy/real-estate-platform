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

### License

All photos come from [Pexels](https://www.pexels.com) and are used under the
[Pexels License](https://www.pexels.com/license/), which allows free use and modification
without attribution. Authors are credited here anyway.

### Credits

| Author | Profile | Photos |
| --- | --- | --- |
| Alesha | https://www.pexels.com/@alesha-260500796/ | 1 |
| Binyamin Mellish | https://www.pexels.com/@binyaminmellish/ | 4 |
| Clay Elliot | https://www.pexels.com/@ibidsy/ | 1 |
| Curtis Adams | https://www.pexels.com/@curtis-adams-1694007/ | 4 |
| Erik Mclean | https://www.pexels.com/@introspectivedsgn/ | 1 |
| Max Vakhtbovych | https://www.pexels.com/@artbovich/ | 5 |
| Nelson Marulanda | https://www.pexels.com/@nelson-marulanda-708254/ | 1 |
| Pixabay | https://www.pexels.com/@pixabay/ | 8 |
| shaza jocarlos | https://www.pexels.com/@shaza-jocarlos-841367558/ | 1 |

The source page of every single photo is in the `page_url` field of the manifest.
