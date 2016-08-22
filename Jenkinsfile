elifePipeline {
    stage 'Checkout'
    checkout scm
    def commit = elifeGitRevision()

    stage 'Project tests'
    lock('api-dummy--ci') {
        builderDeployRevision 'api-dummy--ci', commit
        builderProjectTests 'api-dummy--ci', '/srv/api-dummy', ['/srv/api-dummy/build/phpunit.xml']
    }

    elifeMainlineOnly {
        stage 'Approval'
        elifeGitMoveToBranch commit, 'approved'

        stage 'Deploy on demo'
        elifeGitMoveToBranch commit, 'master'
        builderDeployRevision 'api-dummy--demo', commit
    }
}
