var module = angular.module('app', ['localytics.directives'])
.controller('AppController', function($scope, $http) {
    $scope.type = window.location.hash == '' ? 'futurama' : window.location.hash.replace('#','');

    $scope.$watch('type', function(){
        window.location.hash = $scope.type;
    });

    $(window).on('hashchange', function() {
        if(window.location.hash != $scope.type){
            $scope.$apply(function(){
                var type = window.location.hash.replace('#','');;
                if($('option[value="' + type + '"]').length > 0){
                    $scope.type = type;
                }
            })
        }
    });

    $('#photoshop').click(function(e){
        e.preventDefault();
        $('section.plugin').slideToggle();
        return false;
    });

    $('.copyclip').zclip({
        path:'js/ZeroClipboard.swf',
        copy:function(){
            return 'http://' + $(this).parent().find('.link').text();
        },
        afterCopy:function(){
            var img = $(this).parent();
            img.addClass('copied');
            setTimeout(function(){
                img.removeClass('copied');
            }, 1000);
        }
    });




})
.directive('ngLoad',function() {
    return {
        restrict: 'A',
        link : function(scope, element, attrs) {
            var parent = $(element).parent();
            scope.$watch(attrs['ngLoad'], function(){
                parent.fadeTo(0,0);
                $(element).load(function(){
                    parent.fadeTo(500, 1);
                });
            });
        }
    };
});