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
            stage 'Push image', {
                checkout scm
                image = DockerImage.elifesciences('api-dummy')
                image.push()
            }
        },
        'elife-libraries--ci'
    )

    elifeMainlineOnly {
        stage 'Approval', {
            elifeGitMoveToBranch commit, 'approved'
        }

        stage 'Deploy on demo', {
            elifeGitMoveToBranch commit, 'master'
            builderDeployRevision 'api-dummy--demo', commit
        }
    }
}
