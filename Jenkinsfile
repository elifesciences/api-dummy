elifePipeline {
    def commit
    stage 'Checkout', {
        checkout scm
        commit = elifeGitRevision()
    }

    elifeOnNode(
        {
            stage 'Build image', {
                checkout scm
                sh "docker build -t elifesciences/api-dummy:${commit} ."
            }
        },
        'elife-libraries--ci'
    )

    stage 'Project tests', {
        lock('api-dummy--ci') {
            builderDeployRevision 'api-dummy--ci', commit
            builderProjectTests 'api-dummy--ci', '/srv/api-dummy', ['/srv/api-dummy/build/phpunit.xml']
        }
    }

    def image
    elifeOnNode(
        {
            elifeMainlineOnly {
                stage 'Push image', {
                    checkout scm
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
