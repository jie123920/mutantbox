var gulp = require('gulp');
var browserSync = require('browser-sync').create();
var compass = require('gulp-compass');
var plumber = require('gulp-plumber');
//var inject = require('gulp-inject');
var watch = require('gulp-watch');
var uncss = require('gulp-uncss');
var concat = require('gulp-concat');
var minifyCss = require('gulp-minify-css');
var rev = require('gulp-rev');
var revReplace = require('gulp-rev-replace');
var processhtml = require('gulp-processhtml');
var rimraf = require('rimraf');
var gulpSequence = require('gulp-sequence').use(gulp);
var jsmin = require('gulp-jsmin');
var fs = require('fs');
var makedir = require('makedir');

gulp.task('default', function () {
    console.log(1111);
});

//Public/static
gulp.task('build-static', function() {
    return gulp.src(['./web/Public/src/static/**'])
        .pipe(rev())
        .pipe(gulp.dest('./web/Public/dist/static'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('./dist/rev/static'));

});




// 为图片打版本号
gulp.task('build-Home-images', function() {
    return gulp.src(['./web/Public/src/Home/images/**'])
        .pipe(rev())
        .pipe(gulp.dest('./web/Public/dist/Home/images'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('./dist/rev/Home/images'));

});



// 为图片打版本号
gulp.task('build-Home-videoreg_cdn', function() {
    return gulp.src(['./web/Public/src/Home/videoreg_cdn/**'])
        .pipe(rev())
        .pipe(gulp.dest('./web/Public/dist/Home/videoreg_cdn'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('./dist/rev/Home/videoreg_cdn'));
});

//Game
gulp.task('build-Games-images', function() {
    return gulp.src(['./web/Public/src/Games/images/**'])
        .pipe(rev())
        .pipe(gulp.dest('./web/Public/dist/Games/images'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('./dist/rev/Games/images'));

});



// css home 压缩，打版本号
gulp.task('build-Home-css', function() {
    return gulp.src(['./web/Public/src/Home/css/**'])
        .pipe(minifyCss())
        .pipe(rev())
        .pipe(gulp.dest('./web/Public/dist/Home/css/'))
        .pipe(rev.manifest())
        .pipe(gulp.dest("./dist/rev/Home/css"));
});


gulp.task('build-Home-js', function() {
    return gulp.src('./web/Public/src/Home/js/**')
        .pipe(rev())
        .pipe(gulp.dest('./web/Public/dist/Home/js/'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('./dist/rev/Home/js'));
});


// css Games 压缩，打版本号
gulp.task('build-Games-css', function() {
    return gulp.src(['./web/Public/src/Games/css/**'])
        .pipe(minifyCss())
        .pipe(rev())
        .pipe(gulp.dest('./web/Public/dist/Games/css/'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('./dist/rev/Games/css'));
});


gulp.task('build-Games-js', function() {
    return gulp.src('./web/Public/src/Games/js/**')
        .pipe(rev())
        .pipe(gulp.dest('./web/Public/dist/Games/js/'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('./dist/rev/Games/js'));
});


// 字体

gulp.task('build-Home-font', function() {
    return gulp.src('./web/Public/src/Home/fonts/**')
        .pipe(rev())
        .pipe(gulp.dest('./web/Public/dist/Home/fonts/'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('./dist/rev/Home/fonts'));
});


// font Games 压缩，打版本号
gulp.task('build-Games-font', function() {
    return gulp.src('./web/Public/src/Games/fonts/**')
        .pipe(rev())
        .pipe(gulp.dest('./web/Public/dist/Games/fonts/'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('./dist/rev/Games/fonts'));
});


// 替换HTML模板里的资源版本号
gulp.task('build-replace-views-Home', function() {
    var manifest = gulp.src("./dist/rev/Home/**/*.json");
    return gulp.src(["./modules/home/views/src/**/*.html"])
        .pipe(revReplace({
            manifest: manifest
        }))
        .pipe(gulp.dest('./modules/home/views/dist'));
})


//替换GamesHTML模板 --
gulp.task('build-replace-views-Games', function() {
    var manifest = gulp.src("./dist/rev/Games/**/*.json");
    return gulp.src(["./modules/liberators/views/src/**/*.html"])
        .pipe(revReplace({
            manifest: manifest
        }))
        .pipe(gulp.dest('./modules/liberators/views/dist'));
})


// 替换css里图片资源版本号
gulp.task('build-replace-css-Home', function() {
    var manifest = gulp.src("./dist/rev/Home/**/*.json");
    return gulp.src(["./web/Public/dist/Home/css/**/*.css"])
        .pipe(revReplace({
            manifest: manifest
        }))
        .pipe(gulp.dest('./web/Public/dist/Home/css'));
});

// 替换Gamws css里图片资源版本号
gulp.task('build-replace-css-Games', function() {
    var manifest = gulp.src("./dist/rev/Games/**/*.json");
    return gulp.src(["./web/Public/dist/Games/css/**/*.css"])
        .pipe(revReplace({
            manifest: manifest
        }))
        .pipe(gulp.dest('./web/Public/dist/Games/css'));
});


// 替换fonts里图片资源版本号
gulp.task('build-replace-font-Home', function() {
    var manifest = gulp.src("./dist/rev/Home/**/*.json");
    return gulp.src(["./web/Public/dist/Home/fonts/**/*.*"])
        .pipe(revReplace({
            manifest: manifest
        }))
        .pipe(gulp.dest('./web/Public/dist/Home/fonts'));
});

// 替换Gamws fonts里图片资源版本号
gulp.task('build-replace-font-Games', function() {
    var manifest = gulp.src("./dist/rev/Games/**/*.json");
    return gulp.src(["./web/Public/dist/Games/fonts/**/*.*"])
        .pipe(revReplace({
            manifest: manifest
        }))
        .pipe(gulp.dest('./web/Public/dist/Games/fonts'));
});


gulp.task('build-replace-H-lang', function() {
    var manifest = gulp.src("./dist/rev/Home/images/*.json");
    return gulp.src(["./modules/home/languages/src/**/*.php"])
        .pipe(revReplace({
            manifest: manifest,
            replaceInExtensions:['.js', '.css', '.html', '.php']
        }))
        .pipe(gulp.dest('./modules/home/languages'));
});

gulp.task('build-replace-G-lang', function() {
    var manifest = gulp.src("./dist/rev/Games/images/*.json");
    return gulp.src(["./modules/liberators/languages/src/**/*.php"])
        .pipe(revReplace({
            manifest: manifest,
            replaceInExtensions:['.js', '.css', '.html', '.php']
        }))
        .pipe(gulp.dest('./modules/liberators/languages'));
});


//mutantbox
gulp.task('build-mutantbox', gulp.series('build-Home-images','build-Home-js','build-Home-css','build-Home-font'));
gulp.task('build-replace-mutantbox', gulp.series('build-replace-font-Home','build-replace-H-lang','build-replace-css-Home','build-replace-views-Home'));
//liberators
gulp.task('build-liberators', gulp.series('build-Games-images','build-Games-js','build-Games-css','build-Games-font'));
gulp.task('build-replace-liberators', gulp.series('build-replace-font-Games','build-replace-G-lang','build-replace-css-Games','build-replace-views-Games'));


//--------------------SurvivorLegacy----------------------------
gulp.task('build-sl-font', function() {
    return gulp.src('./web/Public/src/sl/fonts/**')
        .pipe(rev())
        .pipe(gulp.dest('./web/Public/dist/sl/fonts/'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('./dist/rev/sl/fonts'));
});
gulp.task('build-sl-images', function() {
    return gulp.src(['./web/Public/src/sl/images/**'])
        .pipe(rev())
        .pipe(gulp.dest('./web/Public/dist/sl/images'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('./dist/rev/sl/images'));
});
gulp.task('build-sl-css', function() {
    return gulp.src(['./web/Public/src/sl/css/**'])
        .pipe(minifyCss())
        .pipe(rev())
        .pipe(gulp.dest('./web/Public/dist/sl/css/'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('./dist/rev/sl/css'));
});
gulp.task('build-sl-js', function() {
    return gulp.src('./web/Public/src/sl/js/**')
        .pipe(rev())
        .pipe(gulp.dest('./web/Public/dist/sl/js/'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('./dist/rev/sl/js'));
});
gulp.task('build-replace-css-sl', function() {
    var manifest = gulp.src("./dist/rev/sl/**/*.json");
    return gulp.src(["./web/Public/dist/sl/css/**/*.css"])
        .pipe(revReplace({
            manifest: manifest
        }))
        .pipe(gulp.dest('./web/Public/dist/sl/css'));
});
gulp.task('build-replace-SL-lang', function() {
    var manifest = gulp.src("./dist/rev/sl/images/*.json");
    return gulp.src(["./modules/sl/languages/src/**/*.php"])
        .pipe(revReplace({
            manifest: manifest,
            replaceInExtensions:['.js', '.css', '.html', '.php']
        }))
        .pipe(gulp.dest('./modules/sl/languages'));
});

gulp.task('build-replace-font-sl', function() {
    var manifest = gulp.src("./dist/rev/sl/**/*.json");
    return gulp.src(["./web/Public/dist/sl/fonts/**/*.*"])
        .pipe(revReplace({
            manifest: manifest
        }))
        .pipe(gulp.dest('./web/Public/dist/sl/fonts'));
});
gulp.task('build-replace-views-sl', function() {
    var manifest = gulp.src("./dist/rev/sl/**/*.json");
    return gulp.src(["./modules/sl/views/src/**/*.html"])
        .pipe(revReplace({
            manifest: manifest
        }))
        .pipe(gulp.dest('./modules/sl/views/dist'));
})
gulp.task('build-sl', gulp.series('build-sl-font','build-sl-images','build-sl-css','build-sl-js'));
gulp.task('build-replace-sl', gulp.series('build-replace-font-sl','build-replace-css-sl','build-replace-views-sl','build-replace-SL-lang'));
//---------------------SurvivorLegacy-------------------------------------



gulp.task('build-clean-dist', function(callback) {
    return rimraf('./dist', callback);
});

gulp.task('build-clean-rev', function(callback) {
    return rimraf('./dist/rev', callback);
});


gulp.task('build-clean-view', function(callback) {
    return rimraf('./modules/**/view/dist', callback);
});

gulp.task('build-clean-lang', function(callback) {
    return rimraf('./modules/**/languages/*.php', callback);
});

// 清空生产环境文件
gulp.task('build-clean', gulp.series('build-clean-rev', 'build-clean-dist','build-clean-view','build-clean-lang'));


gulp.task('build', gulp.series(
    'build-clean',
    //mutantbox
    'build-mutantbox',
    'build-replace-mutantbox',
    //liberators
    'build-liberators',
    'build-replace-liberators'

    //SurvivorLegacy
    //'build-sl',
    //'build-replace-sl',
));
