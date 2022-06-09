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
                sh "IMAGE_TAG=${commit} docker-compose -f docker-compose.yml build"
                image = DockerImage.elifesciences(this, 'api-dummy', commit)
                elifePullRequestOnly { prNumber ->
                    // push immediately to allow downstream exploration even with automated tests failing
                    image.tag("pr-${prNumber}").push()
                }
            }

            stage 'Project tests', {
                dockerProjectTests 'api-dummy', commit
                try {
                    sh "IMAGE_TAG=${commit} docker-compose -f docker-compose.yml up -d"
                    sh "IMAGE_TAG=${commit} docker-compose -f docker-compose.yml exec -T cli ./smoke_tests.sh"
                } finally {
                    sh 'docker-compose -f docker-compose.yml down'
                }
            }

            elifeMainlineOnly {
                stage 'Push image', {
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
