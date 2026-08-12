# ShareNivo WordPress.org Assets

Upload the contents of `assets/` to the top-level `/assets` directory of the ShareNivo WordPress.org SVN checkout. This directory must sit beside `/trunk` and `/tags`; do not place these files inside the installable plugin ZIP.

Included files:

- `icon.svg`
- `icon-128x128.png`
- `icon-256x256.png`
- `banner-772x250.png`
- `banner-1544x500.png`
- `screenshot-1.png` through `screenshot-5.png`

The matching screenshot captions are already included in the updated plugin `readme.txt`.

Before committing PNG files to SVN, set their MIME type if your SVN client does not do so automatically:

```bash
svn propset svn:mime-type image/png assets/*.png
```

