elifePipeline {
    def commit
    def image
    elifeOnNode(
        {
            stage 'Checkout', {
                checkout scm
                commit = elifeGitRevision()
            }

            stage 'Build image', {
                sh "IMAGE_TAG=${commit} docker-compose -f docker-compose.yml -f docker-compose.ci.yml build"
            }

            stage 'Project tests', {
                try {
                    sh "docker run --name api-dummy_tests_${commit} elifesciences/api-dummy_ci:${commit}"
                } finally {
                    sh "docker cp api-dummy_tests_${commit}:/srv/api-dummy/build/. build"
                    step([$class: "JUnitResultArchiver", testResults: 'build/phpunit.xml'])
                    sh "docker rm api-dummy_tests_${commit}"
                }
                try {
                    sh "IMAGE_TAG=${commit} docker-compose -f docker-compose.yml -f docker-compose.ci.yml up -d"
                    sh "./smoke_tests.sh"
                } finally {
                    sh 'docker-compose -f docker-compose.yml -f docker-compose.ci.yml down'
                }
            }

            elifeMainlineOnly {
                stage 'Push image', {
                    image = DockerImage.elifesciences(this, 'api-dummy', commit)
                    image.push()
                }
            
                stage 'Approval', {
                    elifeGitMoveToBranch commit, 'approved'
                    image.tag('approved').push()
                }
            }
        },
        'elife-libraries--ci'
    )

    elifeMainlineOnly {
        stage 'Deploy on demo', {
            checkout scm
            elifeGitMoveToBranch commit, 'master'
            elifeOnNode(
                {
                    image.tag('latest').push()
                },
                'elife-libraries--ci'
            )
            builderDeployRevision 'api-dummy--demo', commit
        }
    }
}
