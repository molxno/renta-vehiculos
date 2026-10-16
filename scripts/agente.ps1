param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]] $Arguments
)

$denyTools = @(
    '--deny-tool=shell(git push --force)',
    '--deny-tool=shell(gh pr merge)',
    '--deny-tool=shell(git push origin main)',
    '--deny-tool=shell(git push origin qa)',
    '--deny-tool=shell(git push origin dev)'
)

& copilot --allow-all --max-autopilot-continues 20 @denyTools @Arguments
exit $LASTEXITCODE
