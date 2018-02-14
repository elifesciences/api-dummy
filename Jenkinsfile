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
                dockerBuild 'api-dummy', commit
            }

            stage 'Project tests', {
                dockerBuildCi 'api-dummy', commit
                dockerProjectTests 'api-dummy', commit
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
    }

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
