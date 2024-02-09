import { readFileSync } from 'node:fs';
import { basename, dirname, extname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import { deleteAsync } from 'del';
import * as sass from 'sass';
import autoprefixer from 'autoprefixer';
import gulp from 'gulp';
import cheerio from 'gulp-cheerio';
import csso from 'gulp-csso';
import postcss from 'gulp-postcss';
import rename from 'gulp-rename';
import replace from 'gulp-replace';
import gulpSass from 'gulp-sass';
import svgmin from 'gulp-svgmin';
import svgstore from 'gulp-svgstore';
import terser from 'gulp-terser';
import typescript from 'gulp-typescript';
import through from 'through2';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const sassCompiler = gulpSass(sass);

const project = typescript.createProject('tsconfig.json');
const precision = 3;

const logos = [
  {
    name: 'visa-mc',
    icons: [
      'visa',
      'mastercard',
    ],
  },
  {
    name: 'visa-mc-wallet',
    icons: [
      'visa',
      'mastercard',
      'apple-pay',
      'g-pay',
    ],
  },
  {
    name: 'all-schemes',
    icons: [
      'cb',
      'visa',
      'mastercard',
    ],
  },
];
const views = [
  {
    name: 'prefixed',
    start: (start) => start,
    width: (inner, prefix) => inner + prefix,
  },
  {
    name: '',
    start: (start, prefix) => start + prefix,
    width: (inner) => inner,
  },
  {
    name: 'suffixed',
    start: (start, prefix) => start + prefix,
    width: (inner, prefix) => inner + prefix,
  },
  {
    name: 'stancer',
    start: (start, prefix) => start + prefix,
    width: (inner, _prefix, suffix) => inner + suffix,
  },
  {
    name: 'all',
    start: (start) => start,
    width: (inner, prefix, suffix) => inner + prefix + suffix,
  },
];
const sizes = {};

const roundedText = (num) => Number.parseFloat(num).toFixed(precision).replaceAll(/\.?0+$/g, '');
const rounded = (num) => Number.parseFloat(roundedText(num));
const svgSize = ($elem, width, height) => {
  const [svgX, svgY, svgWidth, svgHeight] = $elem.attr('viewBox').split(' ');

  const w = svgWidth - svgX;
  const h = svgHeight - svgY;
  const ratio = h / w;

  if (ratio > 1) {
    if (height === null) {
      height = rounded(width * ratio);
    } else {
      width = rounded(height / ratio);
    }
  } else if (width === null) {
    width = rounded(height / ratio);
  } else {
    height = rounded(width * ratio);
  }

  return {
    height,
    width,
  };
};
const svgViewbox = (size, width, height, x, y) => {
  const posX = rounded(x + (size - width) / 2);
  const posY = rounded(y + (size - height) / 2);

  return {
    posX,
    posY,
    viewbox: [posX, posY, roundedText(width), roundedText(height)].join(' '),
  };
};

export const clean = (cb) => deleteAsync('public', cb);

export const scss = () => {
  return gulp
    .src('src/scss/**/*.scss')
    .pipe(
      sassCompiler({
        includePaths: [join(__dirname, '/../node_modules')],
      }),
    )
    .pipe(postcss([autoprefixer()]))
    .pipe(gulp.dest('public/css'))
    .pipe(csso())
    .pipe(rename({ extname: '.min.css' }))
    .pipe(gulp.dest('public/css'))
  ;
};

export const svg = () => {
  return gulp
    .src('src/svg/*.svg')
    .pipe(svgmin((file) => {
      const prefix = basename(file.relative, extname(file.relative)) + '-';

      return {
        configFile: `${__dirname}/src/svg.js`,
        plugins: [
          {
            name: 'cleanupIDs',
            params: {
              minify: true,
              prefix,
            },
          },
        ],
      };
    }))
    .pipe(gulp.dest('public/svg'))
    .pipe(svgstore())
    .pipe(
      cheerio({
        run: ($) => {
          const $svg = $('svg');
          const $symbols = $('symbol');
          const size = 50;
          const max = 10;
          let count = 0;
          let x = size * 0.75;
          let y = 50;

          $symbols.each(function () {
            const $symbol = $(this);
            const $view = $('<view />');
            const $use = $('<use />');

            const id = $symbol.attr('id');
            const { height, width } = svgSize($symbol, size, size);
            const { posX, posY, viewbox } = svgViewbox(size, width, height, x, y);

            $symbol.attr('id', id + '-map');
            $use
              .attr('href', '#' + id + '-map')
              .attr('x', posX)
              .attr('y', posY)
              .attr('width', width)
              .attr('height', height);
            $view.attr('id', id).attr('viewBox', viewbox);

            $svg.append([$use, $view]);

            if (count++ % max === max - 1) {
              x = rounded(size * 0.75);
              y += rounded(size * 1.25);
            } else {
              x += rounded(size * 1.75);
            }

            if (id === 'stancer') {
              sizes[id] = {
                height,
                width,
              };
            }
          });

          for (const { icons, name } of logos) {
            x = rounded(size * 0.75);
            y += rounded(size * 2);

            const viewX = x;
            const viewY = y;

            let totalWidth = 0;

            const maxHeight = 20;
            const padding = 2;
            const gap = 5;
            const $stancerPrefix = $('#stancer-logo-map');
            const $stancerSuffix = $('#stancer-map');
            const $useStancerPrefix = $('<use />').attr('href', '#stancer-logo-map');
            const $useStancerSuffix = $('<use />').attr('href', '#stancer-map');

            const $sep1 = $('<line stroke="#ccc" stroke-linecap="round"/>')
              .attr('y1', viewY + padding)
              .attr('y2', viewY + (maxHeight - padding))
            ;
            const $sep2 = $('<line stroke="#ccc" stroke-linecap="round"/>')
              .attr('y1', viewY + padding)
              .attr('y2', viewY + (maxHeight - padding))
            ;

            const stancerPrefixSize = svgSize($stancerPrefix, null, maxHeight);
            const stancerSuffixSize = svgSize($stancerSuffix, null, maxHeight - 4.5);

            // First Stancer logo

            $useStancerPrefix
              .attr('x', viewX)
              .attr('y', viewY + (maxHeight - stancerPrefixSize.height) / 2)
              .attr('width', stancerPrefixSize.width)
              .attr('height', stancerPrefixSize.height)
            ;

            totalWidth += stancerPrefixSize.width + gap;

            $svg.append($useStancerPrefix);

            $sep1.attr('x1', viewX + totalWidth).attr('x2', viewX + totalWidth);

            totalWidth += gap;

            $svg.append($sep1);

            // Logos

            for (const icon of icons) {
              const $icon = $(`#${icon}-map`);
              const $useIcon = $('<use />').attr('href', `#${icon}-map`);
              const size = svgSize($icon, null, maxHeight);

              $useIcon
                .attr('x', viewX + totalWidth)
                .attr('y', viewY + (maxHeight - size.height) / 2)
                .attr('width', size.width)
                .attr('height', size.height)
              ;

              totalWidth += size.width + gap;

              $svg.append($useIcon);
            }

            // Last Stancer logo

            $sep2.attr('x1', viewX + totalWidth).attr('x2', viewX + totalWidth);

            totalWidth += gap;

            $svg.append($sep2);

            $useStancerSuffix
              .attr('x', viewX + totalWidth)
              .attr('y', viewY + (maxHeight - stancerSuffixSize.height) / 2)
              .attr('width', stancerSuffixSize.width)
              .attr('height', stancerSuffixSize.height)
            ;

            totalWidth += stancerSuffixSize.width;

            $svg.append($useStancerSuffix);

            // Views

            const prefixWidth = stancerPrefixSize.width + gap * 2;
            const suffixWidth = stancerSuffixSize.width + gap * 2;
            const innerWidth = totalWidth - prefixWidth - suffixWidth;

            for (const view of views) {
              const viewName = [
                name,
                view.name,
              ].filter((value) => value).join('-');
              const viewBox = [
                roundedText(view.start(viewX, prefixWidth)),
                viewY,
                roundedText(view.width(innerWidth, prefixWidth, suffixWidth)),
                maxHeight,
              ];

              sizes[viewName] = {
                height: viewBox[3],
                width: viewBox[2],
              };

              $svg.append($('<view />').attr('id', viewName).attr('viewBox', viewBox.join(' ')));
            }
          }
        },
        parserOptions: {
          xmlMode: true,
        },
      }),
    )
    .pipe(rename('symbols.svg'))
    .pipe(gulp.dest('public/svg'))
    .pipe(
      through.obj(function (file, _enc, cb) {
        const content = [
          '$image-map: (',
        ];

        for (const [name, { height, width }] of Object.entries(sizes)) {
          content.push(`  '${name}': (`);
          content.push(`    'height': ${height},`);
          content.push(`    'width': ${width},`);
          content.push(`  ),`);
        }

        content.push(');');
        content.push('');

        file.contents = Buffer.from(content.join('\n'));
        this.push(file);

        cb();
      }),
    )
    .pipe(rename('_icons.scss'))
    .pipe(gulp.dest('src/scss'))
  ;
};

export const ts = () => {
  const stancerFlat = readFileSync('public/svg/stancer-flat.svg', { encoding: 'utf-8' }).trim();

  return project.src()
    .pipe(project()).js
    .pipe(replace('<svg:stancer-flat>', stancerFlat))
    .pipe(gulp.dest('public/js'))
    .pipe(terser())
    .pipe(rename({ extname: '.min.js' }))
    .pipe(gulp.dest('public/js'))
  ;
};

export const build = gulp.series(clean, svg, gulp.parallel(scss, ts));
export default build;
