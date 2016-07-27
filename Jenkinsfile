elifePipeline {
    stage 'Checkout'
    checkout scm
    def commit = elifeGitRevision()

    stage 'Project tests'
    lock('api-dummy--ci') {
        builderDeployRevision 'api-dummy--ci', commit
        builderProjectTests 'api-dummy--ci', '/srv/api-dummy'
        def phpunitTestArtifact = "${env.BUILD_TAG}.phpunit.xml"
        builderTestArtifact phpunitTestArtifact, 'api-dummy--ci', '/srv/api-dummy/build/phpunit.xml'
        elifeVerifyJunitXml phpunitTestArtifact
    }

    elifeMainlineOnly {
        stage 'Approval'
        elifeGitMoveToBranch commit, 'approved'

        stage 'Deploy on demo'
        elifeGitMoveToBranch commit, 'master'
        builderDeployRevision 'api-dummy--demo', commit
    }
}
