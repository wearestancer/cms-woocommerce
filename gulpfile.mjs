import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import * as sass from 'sass';
import autoprefixer from 'autoprefixer';
import gulp from 'gulp';
import csso from 'gulp-csso';
import postcss from 'gulp-postcss';
import rename from 'gulp-rename';
import gulpSass from 'gulp-sass';
import terser from 'gulp-terser';
import typescript from 'gulp-typescript';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const sassCompiler = gulpSass(sass);

const project = typescript.createProject('tsconfig.json');


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

export const ts = () => {
  return project.src()
    .pipe(project()).js
    .pipe(gulp.dest('public/js'))
    .pipe(terser())
    .pipe(rename({ extname: '.min.js' }))
    .pipe(gulp.dest('public/js'))
  ;
};

export const build = gulp.parallel(scss, ts);
export default build;
